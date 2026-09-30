<?php

declare(strict_types=1);

use Modules\Connection\Api\KeepingReadingsFor;
use Modules\Kernel\Api\HowLongReadingsAreKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeepsReadingsFor;
use Modules\Kernel\Api\KeptFor;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ReadingsKeptForInMemory;

// The KeepsReadingsFor contract, run against the phone's settings and the fake.

/** @return array<string, KeepsReadingsFor> */
function everyKeeperOfHowLong(): array
{
    return [
        'the phone\'s settings' => new KeepingReadingsFor(ASealInMemory::working(), ConnectionSettingsInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))),
        'the fake' => ReadingsKeptForInMemory::standard(),
    ];
}

it('keeps readings for thirty days until the operator chooses', function (): void {
    foreach (everyKeeperOfHowLong() as $which => $keeper) {
        expect($keeper->keptFor()->is(KeptFor::ThirtyDays))->toBeTrue($which);
    }
});

it('keeps the choice, and answers it as in force', function (): void {
    foreach (everyKeeperOfHowLong() as $which => $keeper) {
        expect($keeper->keepFor(HowLongReadingsAreKept::untilRemoved())->isUntilRemoved())->toBeTrue($which)
            ->and($keeper->keptFor()->isUntilRemoved())->toBeTrue($which);
    }
});

it('answers the choice as in force where the phone can keep nothing, and keeps the standard', function (): void {
    foreach ([
        'the phone\'s settings' => new KeepingReadingsFor(ASealInMemory::withNoSecureStorage(), ConnectionSettingsInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))),
        'the fake' => ReadingsKeptForInMemory::keepingNothing(),
    ] as $which => $keeper) {
        expect($keeper->keepFor(HowLongReadingsAreKept::for(KeptFor::SevenDays))->is(KeptFor::SevenDays))->toBeTrue($which)
            ->and($keeper->keptFor()->is(KeptFor::ThirtyDays))->toBeTrue($which);
    }
});
