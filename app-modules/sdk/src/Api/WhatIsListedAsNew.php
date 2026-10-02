<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function filter_var;
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
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\TheNewsOfAStack;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\Sdk\Api\Fields\NewsItemsField;
use Modules\Sdk\Internal\Wire;

use function preg_match;

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

        $unread = self::unread($data);

        return new TheNewsOfAStack(
            in_array(NewsItemsField::Updates->value, $unread, strict: true) ? WhatTheStackListed::unread() : WhatTheStackListed::these(...self::releases($data)),
            in_array(WireField::Requests->value, $unread, strict: true) ? WhatTheStackListed::unread() : WhatTheStackListed::these(...self::requests($data)),
            in_array(NewsItemsField::Problems->value, $unread, strict: true) ? WhatTheStackListed::unread() : WhatTheStackListed::these(...self::problems($data)),
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
     * The kinds the stack could not read, by the words the wire names them with.
     *
     * @param array<array-key, mixed> $data
     *
     * @return list<string>
     */
    private static function unread(array $data): array
    {
        $words = [];

        foreach (self::listOf($data, WireField::Unread) as $word) {
            if (! is_string($word)) {
                throw NewsIsUnreadable::missing(WireField::Unread);
            }

            $words[] = $word;
        }

        return $words;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<AReleaseListed>
     */
    private static function releases(array $data): array
    {
        $read = [];

        foreach (self::listOf($data, NewsItemsField::Updates) as $position => $release) {
            $item = self::entry($release, NewsItemsField::Updates, WireField::Version, $position);
            $version = self::text($item, NewsItemsField::Updates, WireField::Version, $position);
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

        foreach (self::listOf($data, WireField::Requests) as $position => $request) {
            $item = self::entry($request, WireField::Requests, WireField::Number, $position);
            if (! array_key_exists(WireField::Number->value, $item) || ! is_int($item[WireField::Number->value])) {
                throw NewsIsUnreadable::item(WireField::Requests, WireField::Number, $position);
            }

            $number = $item[WireField::Number->value];

            $read[] = new ARequestListed(
                RequestId::numbered($number),
                self::optional($item, WireField::Title),
                self::text($item, WireField::Requests, NewsItemsField::By, $position),
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

        foreach (self::listOf($data, NewsItemsField::Problems) as $position => $problem) {
            $item = self::entry($problem, NewsItemsField::Problems, WireField::Check, $position);
            $onset = self::text($item, NewsItemsField::Problems, NewsItemsField::Onset, $position);

            $seconds = filter_var($onset, FILTER_VALIDATE_INT);

            // Digits and nothing else, as `Records` reads a change's `at`, and
            // within an integer, which a long enough run of digits is not.
            if (preg_match('/^\d+$/', $onset) !== 1 || $seconds === false) {
                throw NewsIsUnreadable::item(NewsItemsField::Problems, NewsItemsField::Onset, $position);
            }

            $read[] = new AProblemListed(
                Check::of(self::text($item, NewsItemsField::Problems, WireField::Check, $position)),
                Instant::atEpochSeconds($seconds),
                self::text($item, NewsItemsField::Problems, WireField::Summary, $position),
            );
        }

        return $read;
    }

    /**
     * One list of the payload, refused where it is not one.
     *
     * @param array<array-key, mixed> $data
     *
     * @return list<mixed>
     */
    private static function listOf(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value]) || ! array_is_list($data[$field->value])) {
            throw NewsIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * One entry of a list, refused where it is not an object.
     *
     * @return array<array-key, mixed>
     */
    private static function entry(mixed $entry, NamesAWireField $list, NamesAWireField $field, int $position): array
    {
        if (! is_array($entry)) {
            throw NewsIsUnreadable::item($list, $field, $position);
        }

        return $entry;
    }

    /**
     * A field every entry of its kind carries as text.
     *
     * @param array<array-key, mixed> $item
     */
    private static function text(array $item, NamesAWireField $list, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $item) || ! is_string($item[$field->value])) {
            throw NewsIsUnreadable::item($list, $field, $position);
        }

        return $item[$field->value];
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
