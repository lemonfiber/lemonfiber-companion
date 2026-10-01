<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Connection\Api\ClearingWhatCannotBeRead;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\Instant;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\HealthReadingsInMemory;
use Tests\Support\Fakes\ReadingsKeptForInMemory;

/**
 * What a screen is handed about what the phone keeps, where a test is about something else.
 *
 * A seal that works over a store holding nothing, which is a phone that has
 * kept nothing yet: a screen built with these opens as it did before anything
 * was kept, so a test about what it draws from a stack is not also a test of
 * what it kept.
 */
final readonly class WhatThePhoneKeeps
{
    /** Deciding what is kept of a stack's health, with nothing kept yet. */
    public static function nothingYet(): KeepingTheLastReading
    {
        return new KeepingTheLastReading(ASealInMemory::working(), HealthReadingsInMemory::empty(), ReadingsKeptForInMemory::standard(), FrozenClock::at(Instant::atEpochSeconds(0)));
    }

    /** Clearing what the phone kept on opening, with nothing kept to clear. */
    public static function nothingToClear(): ClearingWhatCannotBeRead
    {
        return new ClearingWhatCannotBeRead(ASealInMemory::working(), HealthReadingsInMemory::empty());
    }
}
