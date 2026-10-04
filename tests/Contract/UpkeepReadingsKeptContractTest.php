<?php

declare(strict_types=1);

use Modules\Updates\Internal\Store\UpkeepReadingsInTheDatabase;
use Modules\Updates\Internal\UpkeepReadingsKept;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\WhatAStoreOfReadingsPromises;

// The UpkeepReadingsKept contract, run against the adapter and against the fake.
//
// Every test of what `updates` decides about a kept reading runs over
// `ReadingsInMemory`, so what the fake promises is what those tests are
// written against. The promise is every store of readings' promise, written
// once in `WhatAStoreOfReadingsPromises`; this runs it over the store `updates`
// keeps where each stack stands on being up to date in.
//
// The adapter runs over the app's own database in memory, with every migration
// the phone runs run over it, so the table it is tested against is the one its
// migration makes.

/** The table updates keeps its readings in. Named for this file (`G10`). */
const THE_UPDATES_READINGS_TABLE = 'updates_readings';

/**
 * Each updates store, holding nothing yet, made when its turn comes.
 *
 * @return array<string, Closure():UpkeepReadingsKept>
 */
function everyUpdatesStoreHoldingNothing(): array
{
    return [
        'the adapter' => static fn(): UpkeepReadingsInTheDatabase => new UpkeepReadingsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::empty(),
    ];
}

/**
 * Each updates store, holding a row for the first stack that a later build wrote.
 *
 * @return array<string, Closure():UpkeepReadingsKept>
 */
function everyUpdatesStoreHoldingALaterBuildsRow(): array
{
    return [
        'the adapter' => static function (): UpkeepReadingsInTheDatabase {
            $database = AKeptDatabase::migrated();
            WhatAStoreOfReadingsPromises::aRowALaterBuildWroteIn($database, THE_UPDATES_READINGS_TABLE);

            return new UpkeepReadingsInTheDatabase($database);
        },
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::empty()->holdsOneALaterBuildWrote(WhatAStoreOfReadingsPromises::aStack()),
    ];
}

it('finds nothing for a stack it has kept nothing for', function (): void {
    WhatAStoreOfReadingsPromises::findsNothingWhereNothingIsKept(everyUpdatesStoreHoldingNothing());
});

it('hands back the reading it kept, sealed as it came, with its shape and when it was read', function (): void {
    WhatAStoreOfReadingsPromises::handsBackWhatItKeptAsItCame(everyUpdatesStoreHoldingNothing());
});

it('keeps one reading per stack, the newest replacing the one before it and every stack apart', function (): void {
    WhatAStoreOfReadingsPromises::keepsOneReadingPerStack(everyUpdatesStoreHoldingNothing());
});

it('forgets one stack\'s reading and no other\'s', function (): void {
    WhatAStoreOfReadingsPromises::forgetsOneStackAndNoOther(everyUpdatesStoreHoldingNothing());
});

it('forgets every reading read before a moment, and keeps the one read at it', function (): void {
    WhatAStoreOfReadingsPromises::forgetsWhatWasReadBeforeAMoment(everyUpdatesStoreHoldingNothing());
});

it('forgets everything it keeps, for every stack, and says how much that was', function (): void {
    WhatAStoreOfReadingsPromises::forgetsEverythingItKeeps(everyUpdatesStoreHoldingNothing());
});

it('answers a reading a later build wrote as one it cannot read, and keeps a new one over it', function (): void {
    WhatAStoreOfReadingsPromises::answersALaterBuildsRowAsUnreadable(everyUpdatesStoreHoldingALaterBuildsRow());
});

it('forgets a reading it cannot read when asked to forget that stack', function (): void {
    WhatAStoreOfReadingsPromises::forgetsAnUnreadableRowWithItsStack(everyUpdatesStoreHoldingALaterBuildsRow());
});

it('keeps nothing, finds nothing and forgets nothing where it cannot be reached', function (): void {
    WhatAStoreOfReadingsPromises::answersEvenWhereItCannotBeReached([
        'the adapter' => static fn(): UpkeepReadingsInTheDatabase => new UpkeepReadingsInTheDatabase(AKeptDatabase::empty()),
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::unreachable(),
    ]);
});
