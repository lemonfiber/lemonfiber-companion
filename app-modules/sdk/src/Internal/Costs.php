<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Sdk\Api\RosterIsUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * What each verb takes away, read off a listing of what is running.
 *
 * Its own reader rather than more of {@see \Modules\Sdk\Api\Rosters}, and not only because that
 * one had reached the complexity a class is allowed: this answers a different
 * question from the rest of the payload. The services are what the stack *is*
 * doing; this is what doing something to them would cost, which is the sentence
 * has to be said before an operator confirms.
 */
final readonly class Costs
{
    /**
     * What each verb would take away, as the stack reported it.
     *
     * Refused rather than defaulted when it is absent: a
     * verb this side cannot read a bound for is a payload gone wrong, not a
     * verb that costs nothing — and *it costs nothing* is the reassuring answer
     * an operator would confirm on.
     *
     * @param array<mixed> $data
     */
    public static function in(array $data): Disturbances
    {
        if (! array_key_exists(WireField::Disturbs->value, $data)) {
            throw RosterIsUnreadable::missing(WireField::Disturbs);
        }

        $said = $data[WireField::Disturbs->value];

        if (! is_array($said)) {
            throw RosterIsUnreadable::missing(WireField::Disturbs);
        }

        return Disturbances::of(
            self::takenAway($said, WireField::Starting),
            self::takenAway($said, WireField::Stopping),
            self::takenAway($said, WireField::Restarting),
        );
    }

    /**
     * One verb's share of it, in whichever of the two shapes it arrived.
     *
     * The tag decides, rather than which field happens to be present: a payload
     * carrying both would otherwise be read as whichever this side looked for
     * first, and a bound read off the wrong shape is a number nothing honours.
     *
     * @param array<mixed> $said
     */
    private static function takenAway(array $said, WireField $verb): WhatItTakesAway
    {
        if (! array_key_exists($verb->value, $said)) {
            throw RosterIsUnreadable::missing($verb);
        }

        $one = $said[$verb->value];

        if (! is_array($one) || ! array_key_exists(WireField::Bound->value, $one)) {
            throw RosterIsUnreadable::missing(WireField::Bound);
        }

        return $one[WireField::Bound->value] === WireField::Bounded->value
            ? WhatItTakesAway::atMost(self::forHowLong($one, $verb))
            : WhatItTakesAway::until(self::waitingFor($one, $verb));
    }

    /**
     * How long a bounded disturbance runs for.
     *
     * @param array<mixed> $one
     */
    private static function forHowLong(array $one, WireField $verb): int
    {
        if (! array_key_exists(WireField::Seconds->value, $one)) {
            throw RosterIsUnreadable::missing($verb);
        }

        $seconds = $one[WireField::Seconds->value];

        return is_int($seconds) ? $seconds : throw RosterIsUnreadable::missing($verb);
    }

    /**
     * What an unbounded one waits for.
     *
     * @param array<mixed> $one
     */
    private static function waitingFor(array $one, WireField $verb): Awaiting
    {
        if (! array_key_exists(WireField::Until->value, $one)) {
            throw RosterIsUnreadable::missing($verb);
        }

        $until = $one[WireField::Until->value];

        if (! is_string($until)) {
            throw RosterIsUnreadable::missing($verb);
        }

        return Awaiting::tryFrom($until) ?? throw RosterIsUnreadable::missing($verb);
    }
}
