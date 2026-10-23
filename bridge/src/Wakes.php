<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Being woken by the platform while the app is away, through lemonfiber's bridge.
 *
 * Background refresh on iOS and periodic work on Android. Each native half
 * keeps whether it was asked, so a wake the platform holds survives the app
 * being closed and is asked for again at launch; stopping takes it back.
 */
final readonly class Wakes
{
    /** The console command a wake runs, named the same in each native half's `WakeRule`. */
    public const string COMMAND = 'lemonfiber:hear-while-away';

    /** The one word that means the platform took the request. */
    private const string WILL_WAKE = 'will_wake';

    /** The one word that means the platform let go of it. */
    private const string WILL_NOT_WAKE = 'will_not_wake';

    /** Ask to be woken whenever the platform chooses, and answer whether it took the request. */
    public function ask(): bool
    {
        return WhatTheBridgeAnswered::toNothing(Call::WakesAsk)->outcome() === self::WILL_WAKE;
    }

    /** Stop asking, and answer whether the platform let go of the wake it held. */
    public function stop(): bool
    {
        return WhatTheBridgeAnswered::toNothing(Call::WakesStop)->outcome() === self::WILL_NOT_WAKE;
    }
}
