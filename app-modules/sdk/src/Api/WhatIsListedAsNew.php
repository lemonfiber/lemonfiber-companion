<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function in_array;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\NewsItemsEnvelope;
use Modules\Kernel\Api\AProblemListed;
use Modules\Kernel\Api\AReleaseListed;
use Modules\Kernel\Api\ARequestListed;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\TheNewsOfAStack;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\Sdk\Internal\WhatANewsListHolds;
use Modules\Sdk\Internal\Wire;

/**
 * Reads the `news-items` envelope into what a stack lists that could be new.
 *
 * Written the way {@see WhereTheDoorIs} is: a static fold with no state,
 * refusing anything the kernel would refuse, with {@see NewsIsUnreadable}.
 *
 * **A kind the stack names as unread is unread, whatever its list holds.** The
 * stack empties the list of a kind it could not read, and the word is what says
 * the empty list is not everything there is.
 */
final readonly class WhatIsListedAsNew
{
    /**
     * Everything the stack listed, each kind newest first.
     *
     * @param Envelope<mixed> $envelope the `news-items` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheNewsOfAStack
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw NewsIsUnreadable::missing(WireField::Data);
        }

        $unread = WhatANewsListHolds::unread($data);

        return new TheNewsOfAStack(
            in_array(WireField::Updates->value, $unread, strict: true) ? WhatTheStackListed::unread() : WhatTheStackListed::these(...self::releases($data)),
            in_array(WireField::Requests->value, $unread, strict: true) ? WhatTheStackListed::unread() : WhatTheStackListed::these(...self::requests($data)),
            in_array(WireField::Problems->value, $unread, strict: true) ? WhatTheStackListed::unread() : WhatTheStackListed::these(...self::problems($data)),
        );
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason: the generated
     * envelope asserts its shape without checking it.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return NewsItemsEnvelope::in($envelope)->data;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<AReleaseListed>
     */
    private static function releases(array $data): array
    {
        $read = [];

        foreach (WhatANewsListHolds::listOf($data, WireField::Updates) as $position => $release) {
            $item = WhatANewsListHolds::entry($release, WireField::Updates, WireField::Version, $position);
            $version = WhatANewsListHolds::text($item, WireField::Updates, WireField::Version, $position);
            $delivers = self::optional($item, WireField::Delivers);
            $read[] = AReleaseListed::versioned(
                $version,
                $delivers === '' ? WhatAReleaseDelivers::saidNothing() : WhatAReleaseDelivers::said($delivers),
            );
        }

        return $read;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<ARequestListed>
     */
    private static function requests(array $data): array
    {
        $read = [];

        foreach (WhatANewsListHolds::listOf($data, WireField::Requests) as $position => $request) {
            $item = WhatANewsListHolds::entry($request, WireField::Requests, WireField::Number, $position);
            if (! array_key_exists(WireField::Number->value, $item) || ! is_int($item[WireField::Number->value])) {
                throw NewsIsUnreadable::item(WireField::Requests, WireField::Number, $position);
            }

            $number = $item[WireField::Number->value];

            $read[] = new ARequestListed(
                RequestId::numbered($number),
                self::optional($item, WireField::Title),
                WhatANewsListHolds::text($item, WireField::Requests, WireField::By, $position),
            );
        }

        return $read;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<AProblemListed>
     */
    private static function problems(array $data): array
    {
        $read = [];

        foreach (WhatANewsListHolds::listOf($data, WireField::Problems) as $position => $problem) {
            $item = WhatANewsListHolds::entry($problem, WireField::Problems, WireField::Check, $position);

            $read[] = new AProblemListed(
                Check::of(WhatANewsListHolds::text($item, WireField::Problems, WireField::Check, $position)),
                WhatANewsListHolds::onset($item, $position),
                WhatANewsListHolds::text($item, WireField::Problems, WireField::Summary, $position),
            );
        }

        return $read;
    }

    /**
     * A field the contract lets an entry leave out, or empty where it did.
     *
     * Null and absent are both that, and the contract writes both: a release with
     * nothing said of what it delivers, a request no service has a title for yet.
     *
     * @param array<array-key, mixed> $item
     */
    private static function optional(array $item, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $item) || ! is_string($item[$field->value])) {
            return '';
        }

        return $item[$field->value];
    }
}
