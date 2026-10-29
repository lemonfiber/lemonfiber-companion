<?php

declare(strict_types=1);

use Bootstrap\Composition\EveryStoreOfReadings;
use Bootstrap\Composition\NothingToAsk;
use Modules\Health\Internal\HealthReadingsKept;
use Modules\Health\Internal\Store\HealthReadingsInTheDatabase;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\ForgetsOldReadings;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Modules\Requests\Internal\RequestsKept;
use Modules\Requests\Internal\Store\RequestsInTheDatabase;
use Modules\Services\Internal\ListingsKept;
use Modules\Services\Internal\Store\ListingsInTheDatabase;
use Modules\Updates\Internal\Store\UpkeepReadingsInTheDatabase;
use Modules\Updates\Internal\UpkeepReadingsKept;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\WhatEveryKeeperKeeps;
use Tests\Support\WhatTheModulesDeclare;

pest()->group('holds:bootstrap/Composition');

// The ForgetsOldReadings contract, run against every store of readings and
// against all of them together.
//
// Letting go of readings older than the operator chose asks this once, so the
// promise is the whole of what makes that true: after it, no store holds a
// reading read before the moment, every one read at or after it is still
// there, and it says how many it let go of. A store left out of the asking
// would keep a reading past the time the operator chose.

/** The moment the readings here were read around. Named for this file (`G10`). */
const WHEN_THE_OLD_AND_THE_NEW_WERE_READ = 1_790_000_000;

/** A store holding one reading read a second before the moment, and one read at it. */
function holdingAnOldAndANewReading(HealthReadingsKept|UpkeepReadingsKept|ListingsKept|RequestsKept $store): void
{
    $store->keep(SealedStack::of('the-loft'), SealedPayload::of('sealed'), Shape::One, Instant::atEpochSeconds(WHEN_THE_OLD_AND_THE_NEW_WERE_READ - 1));
    $store->keep(SealedStack::of('the-shed'), SealedPayload::of('sealed'), Shape::One, Instant::atEpochSeconds(WHEN_THE_OLD_AND_THE_NEW_WERE_READ));
}

it('lets go of every reading read before the moment, in every store it answers for, and keeps the rest', function (): void {
    // Each made when its turn comes: the adapters stand on one database.
    $made = [
        'the health adapter' => static function (): array {
            $store = new HealthReadingsInTheDatabase(AKeptDatabase::migrated());

            return [$store, [$store]];
        },
        'the updates adapter' => static function (): array {
            $store = new UpkeepReadingsInTheDatabase(AKeptDatabase::migrated());

            return [$store, [$store]];
        },
        'the services adapter' => static function (): array {
            $store = new ListingsInTheDatabase(AKeptDatabase::migrated());

            return [$store, [$store]];
        },
        'the requests adapter' => static function (): array {
            $store = new RequestsInTheDatabase(AKeptDatabase::migrated());

            return [$store, [$store]];
        },
        'the fake' => static function (): array {
            $store = ReadingsInMemory::empty();

            return [$store, [$store]];
        },
        'every store together' => static function (): array {
            $stores = [ReadingsInMemory::empty(), ReadingsInMemory::empty()];

            return [new EveryStoreOfReadings(...$stores), $stores];
        },
    ];

    foreach ($made as $which => $making) {
        [$forgetting, $stores] = $making();

        foreach ($stores as $store) {
            holdingAnOldAndANewReading($store);
        }

        expect($forgetting->forgetOlderThan(Instant::atEpochSeconds(WHEN_THE_OLD_AND_THE_NEW_WERE_READ))->howMany())->toBe(count($stores), $which);

        foreach ($stores as $store) {
            expect($store->newest(SealedStack::of('the-loft'))->holdsARow())->toBeFalse($which)
                ->and($store->newest(SealedStack::of('the-shed'))->holdsARow())->toBeTrue($which);
        }
    }
});

it('refuses to be built with no store to ask', function (): void {
    expect(static fn(): EveryStoreOfReadings => new EveryStoreOfReadings())->toThrow(NothingToAsk::class);
});

it('lets go of old readings in every store the modules declare, when the app lets go of them', function (): void {
    $stores = WhatEveryKeeperKeeps::heldBy(EveryStoreOfReadings::class, 'stores', app(ForgetsOldReadings::class));

    foreach (WhatTheModulesDeclare::theirOwn(ForgetsOldReadings::class) as $declared) {
        expect(array_any($stores, static fn(mixed $store): bool => $store instanceof $declared))->toBeTrue($declared);
    }
});

it('leaves no reading older than the moment in any store the app lets go of readings in', function (): void {
    WhatEveryKeeperKeeps::inTheAppOverStoresOfItsOwn();

    foreach (WhatEveryKeeperKeeps::askedAsOne(app(ForgetsAStack::class)) as $keeper) {
        WhatEveryKeeperKeeps::somethingFor($keeper, WhatEveryKeeperKeeps::aStack('a'));
    }

    $lettingGo = app(ForgetsOldReadings::class);
    $later = Instant::atEpochSeconds(PHP_INT_MAX);

    expect($lettingGo->forgetOlderThan($later)->howMany())->toBeGreaterThan(0);

    foreach (WhatEveryKeeperKeeps::heldBy(EveryStoreOfReadings::class, 'stores', $lettingGo) as $store) {
        assert($store instanceof ForgetsOldReadings);
        expect($store->forgetOlderThan($later)->howMany())->toBe(0, $store::class);
    }
});

it('lets go of nothing where nothing is kept, and says so', function (): void {
    foreach ([
        'the health adapter' => static fn(): ForgetsOldReadings => new HealthReadingsInTheDatabase(AKeptDatabase::migrated()),
        'the updates adapter' => static fn(): ForgetsOldReadings => new UpkeepReadingsInTheDatabase(AKeptDatabase::migrated()),
        'the services adapter' => static fn(): ForgetsOldReadings => new ListingsInTheDatabase(AKeptDatabase::migrated()),
        'the requests adapter' => static fn(): ForgetsOldReadings => new RequestsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => static fn(): ForgetsOldReadings => ReadingsInMemory::empty(),
    ] as $which => $made) {
        expect($made()->forgetOlderThan(Instant::atEpochSeconds(WHEN_THE_OLD_AND_THE_NEW_WERE_READ))->howMany())->toBe(0, $which);
    }
});
