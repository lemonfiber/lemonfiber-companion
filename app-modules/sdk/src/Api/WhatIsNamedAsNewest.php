<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function in_array;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\NewsEnvelope;
use Modules\Kernel\Api\AProblemNamed;
use Modules\Kernel\Api\AReleaseNamed;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\Sdk\Internal\WhatANewsListHolds;
use Modules\Sdk\Internal\Wire;

/**
 * Reads the `news` event into the newest of each kind a stack names.
 *
 * The event stream says it when a listener arrives and whenever it changes,
 * and a tab is marked from it without the items being read. Written the way
 * {@see WhatIsListedAsNew} is, and by the same hands: a static fold with no
 * state, refusing anything the kernel would refuse, with {@see NewsIsUnreadable}.
 *
 * **A kind the stack names as unread is unread, whatever its list holds**, for
 * {@see WhatIsListedAsNew}'s reason.
 */
final readonly class WhatIsNamedAsNewest
{
    /**
     * The newest of each kind, newest first.
     *
     * @param Envelope<mixed> $envelope the `news` envelope, as the stream carried it
     */
    public static function in(Envelope $envelope): TheNewestNamed
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw NewsIsUnreadable::missing(WireField::Data);
        }

        $unread = WhatANewsListHolds::unread($data);

        return new TheNewestNamed(
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
        return NewsEnvelope::in($envelope)->data;
    }

    /**
     * Each release by its version, which is all the event names of one.
     *
     * @param array<array-key, mixed> $data
     *
     * @return list<AReleaseNamed>
     */
    private static function releases(array $data): array
    {
        $read = [];

        foreach (WhatANewsListHolds::listOf($data, WireField::Updates) as $position => $version) {
            if (! is_string($version)) {
                throw NewsIsUnreadable::item(WireField::Updates, WireField::Version, $position);
            }

            $read[] = AReleaseNamed::versioned($version);
        }

        return $read;
    }

    /**
     * Each request by its number, which is all the event names of one.
     *
     * @param array<array-key, mixed> $data
     *
     * @return list<RequestId>
     */
    private static function requests(array $data): array
    {
        $read = [];

        foreach (WhatANewsListHolds::listOf($data, WireField::Requests) as $position => $number) {
            if (! is_int($number)) {
                throw NewsIsUnreadable::item(WireField::Requests, WireField::Number, $position);
            }

            $read[] = RequestId::numbered($number);
        }

        return $read;
    }

    /**
     * Each check found wrong by the check and its onset.
     *
     * @param array<array-key, mixed> $data
     *
     * @return list<AProblemNamed>
     */
    private static function problems(array $data): array
    {
        $read = [];

        foreach (WhatANewsListHolds::listOf($data, WireField::Problems) as $position => $problem) {
            $item = WhatANewsListHolds::entry($problem, WireField::Problems, WireField::Check, $position);

            $read[] = new AProblemNamed(
                Check::of(WhatANewsListHolds::text($item, WireField::Problems, WireField::Check, $position)),
                WhatANewsListHolds::onset($item, $position),
            );
        }

        return $read;
    }
}
