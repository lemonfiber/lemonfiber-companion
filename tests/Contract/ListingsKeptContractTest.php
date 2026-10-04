<?php

declare(strict_types=1);

use Modules\Services\Internal\ListingsKept;
use Modules\Services\Internal\Store\ListingsInTheDatabase;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\WhatAStoreOfReadingsPromises;

// The ListingsKept contract, run against the adapter and against the fake.
//
// Every test of what `services` decides about a kept listing runs over
// `ReadingsInMemory`, so what the fake promises is what those tests are
// written against. The promise is every store of readings' promise, written
// once in `WhatAStoreOfReadingsPromises`; this runs it over the store `services`
// keeps what each stack runs in.
//
// The adapter runs over the app's own database in memory, with every migration
// the phone runs run over it, so the table it is tested against is the one its
// migration makes.

/** The table services keeps its listings in. Named for this file (`G10`). */
const THE_SERVICES_READINGS_TABLE = 'services_readings';

/**
 * Each services store, holding nothing yet, made when its turn comes.
 *
 * @return array<string, Closure():ListingsKept>
 */
function everyServicesStoreHoldingNothing(): array
{
    return [
        'the adapter' => static fn(): ListingsInTheDatabase => new ListingsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::empty(),
    ];
}

/**
 * Each services store, holding a row for the first stack that a later build wrote.
 *
 * @return array<string, Closure():ListingsKept>
 */
function everyServicesStoreHoldingALaterBuildsRow(): array
{
    return [
        'the adapter' => static function (): ListingsInTheDatabase {
            $database = AKeptDatabase::migrated();
            WhatAStoreOfReadingsPromises::aRowALaterBuildWroteIn($database, THE_SERVICES_READINGS_TABLE);

            return new ListingsInTheDatabase($database);
        },
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::empty()->holdsOneALaterBuildWrote(WhatAStoreOfReadingsPromises::aStack()),
    ];
}

it('finds nothing for a stack it has kept nothing for', function (): void {
    WhatAStoreOfReadingsPromises::findsNothingWhereNothingIsKept(everyServicesStoreHoldingNothing());
});

it('hands back the reading it kept, sealed as it came, with its shape and when it was read', function (): void {
    WhatAStoreOfReadingsPromises::handsBackWhatItKeptAsItCame(everyServicesStoreHoldingNothing());
});

it('keeps one reading per stack, the newest replacing the one before it and every stack apart', function (): void {
    WhatAStoreOfReadingsPromises::keepsOneReadingPerStack(everyServicesStoreHoldingNothing());
});

it('forgets one stack\'s reading and no other\'s', function (): void {
    WhatAStoreOfReadingsPromises::forgetsOneStackAndNoOther(everyServicesStoreHoldingNothing());
});

it('forgets every reading read before a moment, and keeps the one read at it', function (): void {
    WhatAStoreOfReadingsPromises::forgetsWhatWasReadBeforeAMoment(everyServicesStoreHoldingNothing());
});

it('forgets everything it keeps, for every stack, and says how much that was', function (): void {
    WhatAStoreOfReadingsPromises::forgetsEverythingItKeeps(everyServicesStoreHoldingNothing());
});

it('answers a reading a later build wrote as one it cannot read, and keeps a new one over it', function (): void {
    WhatAStoreOfReadingsPromises::answersALaterBuildsRowAsUnreadable(everyServicesStoreHoldingALaterBuildsRow());
});

it('forgets a reading it cannot read when asked to forget that stack', function (): void {
    WhatAStoreOfReadingsPromises::forgetsAnUnreadableRowWithItsStack(everyServicesStoreHoldingALaterBuildsRow());
});

it('keeps nothing, finds nothing and forgets nothing where it cannot be reached', function (): void {
    WhatAStoreOfReadingsPromises::answersEvenWhereItCannotBeReached([
        'the adapter' => static fn(): ListingsInTheDatabase => new ListingsInTheDatabase(AKeptDatabase::empty()),
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::unreachable(),
    ]);
});
