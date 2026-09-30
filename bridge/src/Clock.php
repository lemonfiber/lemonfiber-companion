<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * The phone's clock, as far as this plugin reads it: which zone it is set to.
 *
 * The PHP face of `ClockFunctions` in Kotlin and Swift. One call, and nothing
 * decided here beyond reading the answer: the name the platform gives, or an
 * empty one where nothing answered.
 *
 * **Off a handset the name is empty.** Every desktop and every test run has no
 * bridge, and an empty name is what lets whoever asked fall back to a zone of
 * their own choosing rather than to one this class invented.
 */
final readonly class Clock
{
    /** The zone's name in the time zone database, or empty where none was given. */
    public function zone(): string
    {
        return WhatTheBridgeAnswered::toNothing(Call::Zone)->word(WhatAnAnswerHolds::Zone) ?? '';
    }
}
