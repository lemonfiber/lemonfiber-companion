<?php

declare(strict_types=1);

namespace Tests\Support;

use Bootstrap\Composition\EveryKeeperOfAStack;
use Modules\Connection\Api\ClearingWhatCannotBeRead;
use Modules\Connection\Api\RemovingAStack;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;

use function str_repeat;

use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\HealthReadingsInMemory;
use Tests\Support\Fakes\ReadingsKeptForInMemory;
use Tests\Support\Fakes\RemovalsUnderWayInMemory;
use Tests\Support\Fakes\StandingsInMemory;

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

    /** Clearing what the phone kept on opening, where its key has gone since the launch before and a word was kept. */
    public static function clearedAtOpening(): ClearingWhatCannotBeRead
    {
        $seal = ASealInMemory::working();
        $seal->standing();
        $kept = StandingsInMemory::working();
        $kept->remember(StackId::of(Nonce::of(str_repeat('f', Nonce::SHORTEST))), HowItStands::Healthy, Instant::atEpochSeconds(0));

        return new ClearingWhatCannotBeRead($seal->losesItsKeys(), $kept);
    }

    /** Removing a stack, with nothing left from a removal before. */
    public static function nothingToFinish(): RemovingAStack
    {
        return new RemovingAStack(RemovalsUnderWayInMemory::working(), new EveryKeeperOfAStack());
    }
}
