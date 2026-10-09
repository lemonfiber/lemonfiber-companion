<?php

declare(strict_types=1);

use Modules\Watching\Internal\LanguagesKept;
use Modules\Watching\Internal\Store\LanguagesInTheDatabase;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\WhatAStoreOfReadingsPromises;

// The LanguagesKept contract, run against the adapter and against the fake.
//
// What `watching` decides about a member's choice of languages is tested
// over `ReadingsInMemory`, so what the fake promises is what those tests are
// written against. The promise is every sealed store's, written once in
// `WhatAStoreOfReadingsPromises`, less forgetting by age: a choice is not a
// reading, and grows no older.

/** The table watching keeps the languages members chose in. Named for this file (`G10`). */
const THE_WATCHING_LANGUAGES_TABLE = 'watching_languages';

/**
 * Each languages store, holding nothing yet, made when its turn comes.
 *
 * @return array<string, Closure():LanguagesKept>
 */
function everyLanguagesStoreHoldingNothing(): array
{
    return [
        'the adapter' => static fn(): LanguagesInTheDatabase => new LanguagesInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::empty(),
    ];
}

/**
 * Each languages store, holding a row for the first stack that a later build wrote.
 *
 * @return array<string, Closure():LanguagesKept>
 */
function everyLanguagesStoreHoldingALaterBuildsRow(): array
{
    return [
        'the adapter' => static function (): LanguagesInTheDatabase {
            $database = AKeptDatabase::migrated();
            WhatAStoreOfReadingsPromises::aRowALaterBuildWroteIn($database, THE_WATCHING_LANGUAGES_TABLE);

            return new LanguagesInTheDatabase($database);
        },
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::empty()->holdsOneALaterBuildWrote(WhatAStoreOfReadingsPromises::aStack()),
    ];
}

it('finds nothing for a stack it has kept nothing for', function (): void {
    WhatAStoreOfReadingsPromises::findsNothingWhereNothingIsKept(everyLanguagesStoreHoldingNothing());
});

it('hands back the choice it kept, sealed as it came, with its shape and when it was chosen', function (): void {
    WhatAStoreOfReadingsPromises::handsBackWhatItKeptAsItCame(everyLanguagesStoreHoldingNothing());
});

it('keeps one choice per stack, the newest replacing the one before it and every stack apart', function (): void {
    WhatAStoreOfReadingsPromises::keepsOneReadingPerStack(everyLanguagesStoreHoldingNothing());
});

it('forgets one stack\'s choice and no other\'s', function (): void {
    WhatAStoreOfReadingsPromises::forgetsOneStackAndNoOther(everyLanguagesStoreHoldingNothing());
});

it('forgets everything it keeps, for every stack, and says how much that was', function (): void {
    WhatAStoreOfReadingsPromises::forgetsEverythingItKeeps(everyLanguagesStoreHoldingNothing());
});

it('answers a choice a later build wrote as one it cannot read, and keeps a new one over it', function (): void {
    WhatAStoreOfReadingsPromises::answersALaterBuildsRowAsUnreadable(everyLanguagesStoreHoldingALaterBuildsRow());
});

it('forgets a choice it cannot read when asked to forget that stack', function (): void {
    WhatAStoreOfReadingsPromises::forgetsAnUnreadableRowWithItsStack(everyLanguagesStoreHoldingALaterBuildsRow());
});

it('keeps nothing, finds nothing and forgets nothing where it cannot be reached', function (): void {
    WhatAStoreOfReadingsPromises::answersEvenWhereItCannotBeReached([
        'the adapter' => static fn(): LanguagesInTheDatabase => new LanguagesInTheDatabase(AKeptDatabase::empty()),
        'the fake' => static fn(): ReadingsInMemory => ReadingsInMemory::unreachable(),
    ]);
});
