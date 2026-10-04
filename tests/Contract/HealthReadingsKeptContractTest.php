<?php

declare(strict_types=1);

use Modules\Health\Internal\HealthReadingsKept;
use Modules\Health\Internal\Store\HealthReadingsInTheDatabase;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\WhatAStoreOfReadingsPromises;

// The HealthReadingsKept contract, run against the adapter and against the fake.
//
// Every test of what `health` decides about kept readings runs over
// `ReadingsInMemory`, so what the fake promises is what those tests are
// written against. The promise is every store of readings' promise, written
// once in `WhatAStoreOfReadingsPromises`; this runs it over health's own.
//
// The adapter runs over the app's own database in memory, with every migration
// the phone runs run over it, so the table it is tested against is the one its
// migration makes.

/** The table health keeps its readings in. Named for this file (`G10`). */
const THE_HEALTH_READINGS_TABLE = 'health_readings';

/**
 * Each health store, holding nothing yet, made when its turn comes.
 *
 * @return array<string, Closure():HealthReadingsKept>
 */
function everyHealthStoreHoldingNothing(): array
{
    return [
        'the adapter' => static fn(): HealthReadingsInTheDatabase => new HealthReadingsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::empty(),
    ];
}

/**
 * Each health store, holding a row for the first stack that a later build wrote.
 *
 * @return array<string, Closure():HealthReadingsKept>
 */
function everyHealthStoreHoldingALaterBuildsRow(): array
{
    return [
        'the adapter' => static function (): HealthReadingsInTheDatabase {
            $database = AKeptDatabase::migrated();
            WhatAStoreOfReadingsPromises::aRowALaterBuildWroteIn($database, THE_HEALTH_READINGS_TABLE);

            return new HealthReadingsInTheDatabase($database);
        },
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::empty()->holdsOneALaterBuildWrote(WhatAStoreOfReadingsPromises::aStack()),
    ];
}

it('finds nothing for a stack it has kept nothing for', function (): void {
    WhatAStoreOfReadingsPromises::findsNothingWhereNothingIsKept(everyHealthStoreHoldingNothing());
});

it('hands back the reading it kept, sealed as it came, with its shape and when it was read', function (): void {
    WhatAStoreOfReadingsPromises::handsBackWhatItKeptAsItCame(everyHealthStoreHoldingNothing());
});

it('keeps one reading per stack, the newest replacing the one before it and every stack apart', function (): void {
    WhatAStoreOfReadingsPromises::keepsOneReadingPerStack(everyHealthStoreHoldingNothing());
});

it('forgets one stack\'s reading and no other\'s', function (): void {
    WhatAStoreOfReadingsPromises::forgetsOneStackAndNoOther(everyHealthStoreHoldingNothing());
});

it('forgets every reading read before a moment, and keeps the one read at it', function (): void {
    WhatAStoreOfReadingsPromises::forgetsWhatWasReadBeforeAMoment(everyHealthStoreHoldingNothing());
});

it('forgets everything it keeps, for every stack, and says how much that was', function (): void {
    WhatAStoreOfReadingsPromises::forgetsEverythingItKeeps(everyHealthStoreHoldingNothing());
});

it('answers a reading a later build wrote as one it cannot read, and keeps a new one over it', function (): void {
    WhatAStoreOfReadingsPromises::answersALaterBuildsRowAsUnreadable(everyHealthStoreHoldingALaterBuildsRow());
});

it('forgets a reading it cannot read when asked to forget that stack', function (): void {
    WhatAStoreOfReadingsPromises::forgetsAnUnreadableRowWithItsStack(everyHealthStoreHoldingALaterBuildsRow());
});

it('keeps nothing, finds nothing and forgets nothing where it cannot be reached', function (): void {
    WhatAStoreOfReadingsPromises::answersEvenWhereItCannotBeReached([
        'the adapter' => static fn(): HealthReadingsInTheDatabase => new HealthReadingsInTheDatabase(AKeptDatabase::empty()),
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::unreachable(),
    ]);
});
