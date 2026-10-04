<?php

declare(strict_types=1);

use Bootstrap\Composition\EveryStoreOfReadings;
use Modules\Health\Internal\HealthReadingsKept;
use Modules\Health\Internal\Store\HealthReadingsInTheDatabase;
use Modules\Kernel\Api\ForgetsOldReadings;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Modules\Services\Internal\ListingsKept;
use Modules\Services\Internal\Store\ListingsInTheDatabase;
use Modules\Updates\Internal\Store\UpkeepReadingsInTheDatabase;
use Modules\Updates\Internal\UpkeepReadingsKept;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\ReadingsInMemory;

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
function holdingAnOldAndANewReading(HealthReadingsKept|UpkeepReadingsKept|ListingsKept $store): void
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

it('lets go of nothing where nothing is kept, and says so', function (): void {
    foreach ([
        'the health adapter' => static fn(): ForgetsOldReadings => new HealthReadingsInTheDatabase(AKeptDatabase::migrated()),
        'the updates adapter' => static fn(): ForgetsOldReadings => new UpkeepReadingsInTheDatabase(AKeptDatabase::migrated()),
        'the services adapter' => static fn(): ForgetsOldReadings => new ListingsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => static fn(): ForgetsOldReadings => ReadingsInMemory::empty(),
        'no store at all' => static fn(): ForgetsOldReadings => new EveryStoreOfReadings(),
    ] as $which => $made) {
        expect($made()->forgetOlderThan(Instant::atEpochSeconds(WHEN_THE_OLD_AND_THE_NEW_WERE_READ))->howMany())->toBe(0, $which);
    }
});
