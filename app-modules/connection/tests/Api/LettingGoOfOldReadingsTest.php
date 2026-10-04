<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\LettingGoOfOldReadings;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeptFor;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\SecondsIn;
use Modules\Kernel\Api\Shape;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\Fakes\ReadingsKeptForInMemory;

/** The moment the oldest reading in these tests was read at. */
const WHEN_THE_OLDEST_WAS_READ = 1_790_000_000;

/** Thirty days, in seconds: how long a reading is kept until the operator chooses. */
const THIRTY_DAYS_IN_SECONDS = 2_592_000;

/** A moment, counted in seconds from when the oldest reading was read. */
function secondsAfterTheOldest(int $seconds): Instant
{
    return Instant::atEpochSeconds(WHEN_THE_OLDEST_WAS_READ + $seconds);
}

/** A store holding a reading for the loft, read then, and one for the shed, read a second later. */
function aStoreReadThen(): ReadingsInMemory
{
    $store = ReadingsInMemory::empty();
    $store->keep(SealedStack::of('the-loft'), SealedPayload::of('sealed'), Shape::One, secondsAfterTheOldest(0));
    $store->keep(SealedStack::of('the-shed'), SealedPayload::of('sealed'), Shape::One, secondsAfterTheOldest(1));

    return $store;
}

/** Letting go of what a store keeps, by a period in memory, with the clock reading this. */
function lettingGoOf(ReadingsInMemory $store, Instant $now, ?ReadingsKeptForInMemory $period = null): LettingGoOfOldReadings
{
    return new LettingGoOfOldReadings($period ?? ReadingsKeptForInMemory::standard(), $store, FrozenClock::at($now));
}

it('keeps readings for thirty days until the operator chooses', function (): void {
    expect(lettingGoOf(ReadingsInMemory::empty(), secondsAfterTheOldest(0))->keptFor()->is(KeptFor::ThirtyDays))->toBeTrue();
});

it('lets go of every reading kept longer than thirty days, and keeps one read exactly thirty days ago', function (): void {
    $store = aStoreReadThen();

    expect(lettingGoOf($store, secondsAfterTheOldest(0))->forgetTheOld(secondsAfterTheOldest(THIRTY_DAYS_IN_SECONDS + 1))->howMany())->toBe(1)
        ->and($store->newest(SealedStack::of('the-loft'))->holdsARow())->toBeFalse()
        ->and($store->newest(SealedStack::of('the-shed'))->holdsARow())->toBeTrue();
});

it('lets go of nothing at a moment less than thirty days after the clock began', function (): void {
    $store = ReadingsInMemory::empty();
    $store->keep(SealedStack::of('the-loft'), SealedPayload::of('sealed'), Shape::One, Instant::atEpochSeconds(0));

    expect(lettingGoOf($store, Instant::atEpochSeconds(0))->forgetTheOld(Instant::atEpochSeconds(THIRTY_DAYS_IN_SECONDS - 1))->howMany())->toBe(0);
});

it('keeps the operator\'s choice, and lets go of every reading older than it at once', function (): void {
    $store = aStoreReadThen();
    $store->keep(SealedStack::of('the-shed'), SealedPayload::of('sealed'), Shape::One, secondsAfterTheOldest(2 * SecondsIn::ADay->value));
    $letting = lettingGoOf($store, secondsAfterTheOldest(8 * SecondsIn::ADay->value));

    expect($letting->keepFor(HowLongReadingsAreKept::for(KeptFor::SevenDays))->is(KeptFor::SevenDays))->toBeTrue()
        ->and($letting->keptFor()->is(KeptFor::SevenDays))->toBeTrue()
        ->and($store->newest(SealedStack::of('the-loft'))->holdsARow())->toBeFalse()
        ->and($store->newest(SealedStack::of('the-shed'))->holdsARow())->toBeTrue();
});

it('lets go of the old by the length the operator chose', function (): void {
    $store = ReadingsInMemory::empty();
    $letting = lettingGoOf($store, secondsAfterTheOldest(0));
    $letting->keepFor(HowLongReadingsAreKept::days(3));
    $store->keep(SealedStack::of('the-loft'), SealedPayload::of('sealed'), Shape::One, secondsAfterTheOldest(0));

    expect($letting->forgetTheOld(secondsAfterTheOldest(3 * SecondsIn::ADay->value))->howMany())->toBe(0)
        ->and($letting->forgetTheOld(secondsAfterTheOldest(3 * SecondsIn::ADay->value + 1))->howMany())->toBe(1);
});

it('lets go of no reading for its age where they are kept until removed', function (): void {
    $store = aStoreReadThen();
    $letting = lettingGoOf($store, secondsAfterTheOldest(10 * THIRTY_DAYS_IN_SECONDS));

    expect($letting->keepFor(HowLongReadingsAreKept::untilRemoved())->isUntilRemoved())->toBeTrue()
        ->and($letting->forgetTheOld(secondsAfterTheOldest(10 * THIRTY_DAYS_IN_SECONDS))->howMany())->toBe(0)
        ->and($store->forgetEverything()->howMany())->toBe(2);
});

it('lets go by the choice in force where the phone could not keep it', function (): void {
    $store = aStoreReadThen();
    $letting = lettingGoOf($store, secondsAfterTheOldest(8 * SecondsIn::ADay->value), ReadingsKeptForInMemory::keepingNothing());

    expect($letting->keepFor(HowLongReadingsAreKept::for(KeptFor::SevenDays))->is(KeptFor::SevenDays))->toBeTrue()
        ->and($letting->keptFor()->is(KeptFor::ThirtyDays))->toBeTrue()
        ->and($store->forgetEverything()->howMany())->toBe(0);
});
