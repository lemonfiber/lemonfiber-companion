<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Modules\Health\Internal\HealthReadingsKept;
use Modules\Health\Internal\NewestHealthReading;
use Modules\Health\Internal\Store\HealthReadingsInTheDatabase;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\HealthReadingsInMemory;

// The HealthReadingsKept contract, run against the adapter and against the fake.
//
// Every test of what `health` decides about kept readings runs over
// `HealthReadingsInMemory`, so what the fake promises is what those tests are
// written against: one reading per stack with the later replacing the earlier,
// a reading read exactly at the cut-off kept, and a row this build cannot read
// said to be one. A fake that kept every reading, or pruned the one at the
// cut-off, would make those tests green about a phone that opens on the wrong
// summary or forgets one it was meant to keep.
//
// The adapter runs over the app's own database in memory, with every migration
// the phone runs run over it, so the table it is tested against is the one its
// migration makes.

/** The moment the assertions below are anchored at. */
const WHEN_THE_READING_WAS_TAKEN = 1_790_000_000;

/** A stack as a store names it: its keyed hash. */
function aStackAsTheStoreNamesIt(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-loft');
}

/** A second stack, whose reading must never answer for the first's. */
function anotherStackAsTheStoreNamesIt(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-shed');
}

/** A moment, counted in seconds from the one a reading was taken at. */
function secondsAfterTheReading(int $seconds): Instant
{
    return Instant::atEpochSeconds(WHEN_THE_READING_WAS_TAKEN + $seconds);
}

/**
 * Each store, holding nothing yet.
 *
 * @return array<string, HealthReadingsKept>
 */
function everyHealthStoreHoldingNothing(): array
{
    return [
        'the adapter' => new HealthReadingsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => HealthReadingsInMemory::empty(),
    ];
}

/** Which arm the newest reading answered on, and what it carried, as one word. */
function whatTheStoreFound(NewestHealthReading $newest): string
{
    return $newest->either(
        found: static fn(SealedPayload $payload, Shape $shape, Instant $readAt): Code
            => Code::of(sprintf('%s:%d:%d', $payload->forTheStore(), $shape->value, $readAt->epochSeconds() - WHEN_THE_READING_WAS_TAKEN)),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();
}

/** When a keep was noted as written, or that it was not, as one word. */
function whenItWasNoted(HealthReadingsKept $store, SealedStack $stack, string $payload, int $seconds): string
{
    return $store->keep($stack, SealedPayload::of($payload), Shape::One, secondsAfterTheReading($seconds))->either(
        down: static fn(Instant $at): Code => Code::of(sprintf('down:%d', $at->epochSeconds() - WHEN_THE_READING_WAS_TAKEN)),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('finds nothing for a stack it has kept nothing for', function (): void {
    foreach (everyHealthStoreHoldingNothing() as $which => $store) {
        expect(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('none', $which);
    }
});

it('hands back the reading it kept, sealed as it came, with its shape and when it was read', function (): void {
    foreach (everyHealthStoreHoldingNothing() as $which => $store) {
        expect(whenItWasNoted($store, aStackAsTheStoreNamesIt(), 'sealed-summary', 5))->toBe('down:5', $which)
            ->and(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('sealed-summary:1:5', $which);
    }
});

it('keeps one reading per stack, the newest replacing the one before it', function (): void {
    foreach (everyHealthStoreHoldingNothing() as $which => $store) {
        whenItWasNoted($store, aStackAsTheStoreNamesIt(), 'the-first-summary', 5);
        whenItWasNoted($store, aStackAsTheStoreNamesIt(), 'the-second-summary', 60);

        expect(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('the-second-summary:1:60', $which)
            ->and($store->forgetEverything()->howMany())->toBe(1, $which);
    }
});

it('keeps each stack\'s reading apart from every other\'s', function (): void {
    foreach (everyHealthStoreHoldingNothing() as $which => $store) {
        whenItWasNoted($store, aStackAsTheStoreNamesIt(), 'the-lofts-summary', 5);
        whenItWasNoted($store, anotherStackAsTheStoreNamesIt(), 'the-sheds-summary', 10);

        expect(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('the-lofts-summary:1:5', $which)
            ->and(whatTheStoreFound($store->newest(anotherStackAsTheStoreNamesIt())))->toBe('the-sheds-summary:1:10', $which);
    }
});

it('forgets one stack\'s reading and no other\'s', function (): void {
    foreach (everyHealthStoreHoldingNothing() as $which => $store) {
        whenItWasNoted($store, aStackAsTheStoreNamesIt(), 'the-lofts-summary', 5);
        whenItWasNoted($store, anotherStackAsTheStoreNamesIt(), 'the-sheds-summary', 10);

        expect($store->forget(aStackAsTheStoreNamesIt())->howMany())->toBe(1, $which)
            ->and(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('none', $which)
            ->and(whatTheStoreFound($store->newest(anotherStackAsTheStoreNamesIt())))->toBe('the-sheds-summary:1:10', $which)
            ->and($store->forget(aStackAsTheStoreNamesIt())->howMany())->toBe(0, $which);
    }
});

it('forgets every reading read before a moment, and keeps the one read at it', function (): void {
    // The boundary is the decision: a reading exactly as old as the lifetime
    // has not outlived it yet.
    foreach (everyHealthStoreHoldingNothing() as $which => $store) {
        whenItWasNoted($store, aStackAsTheStoreNamesIt(), 'read-before-the-moment', 99);
        whenItWasNoted($store, anotherStackAsTheStoreNamesIt(), 'read-at-the-moment', 100);

        expect($store->forgetOlderThan(secondsAfterTheReading(100))->howMany())->toBe(1, $which)
            ->and(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('none', $which)
            ->and(whatTheStoreFound($store->newest(anotherStackAsTheStoreNamesIt())))->toBe('read-at-the-moment:1:100', $which);
    }
});

it('forgets everything it keeps, for every stack, and says how much that was', function (): void {
    foreach (everyHealthStoreHoldingNothing() as $which => $store) {
        whenItWasNoted($store, aStackAsTheStoreNamesIt(), 'the-lofts-summary', 5);
        whenItWasNoted($store, anotherStackAsTheStoreNamesIt(), 'the-sheds-summary', 10);

        expect($store->forgetEverything()->howMany())->toBe(2, $which)
            ->and(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('none', $which)
            ->and(whatTheStoreFound($store->newest(anotherStackAsTheStoreNamesIt())))->toBe('none', $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

/** A row for the first stack that a later build wrote, in the adapter's own table. */
function aRowALaterBuildWroteIn(ConnectionInterface $database): void
{
    $database->table('health_readings')->insert([
        'stack_hash' => aStackAsTheStoreNamesIt()->forTheStore(),
        'shape' => 99,
        'read_at' => WHEN_THE_READING_WAS_TAKEN,
        'payload' => 'written-by-a-later-build',
    ]);
}

it('answers a reading a later build wrote as one it cannot read, and keeps a new one over it', function (): void {
    $database = AKeptDatabase::migrated();
    aRowALaterBuildWroteIn($database);

    $stores = [
        'the adapter' => new HealthReadingsInTheDatabase($database),
        'the fake' => HealthReadingsInMemory::empty()->holdsOneALaterBuildWrote(aStackAsTheStoreNamesIt()),
    ];

    foreach ($stores as $which => $store) {
        expect(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('unreadable', $which)
            ->and(whatTheStoreFound($store->newest(anotherStackAsTheStoreNamesIt())))->toBe('none', $which);

        whenItWasNoted($store, aStackAsTheStoreNamesIt(), 'written-by-this-build', 20);

        expect(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('written-by-this-build:1:20', $which);
    }
});

it('keeps nothing, finds nothing and forgets nothing where it cannot be reached', function (): void {
    // A database whose table is not there is the case the adapter can meet on
    // a phone: every query it asks is refused, and every refusal is an answer
    // rather than an exception crossing into `health`.
    $stores = [
        'the adapter' => new HealthReadingsInTheDatabase(AKeptDatabase::empty()),
        'the fake' => HealthReadingsInMemory::unreachable(),
    ];

    foreach ($stores as $which => $store) {
        expect(whenItWasNoted($store, aStackAsTheStoreNamesIt(), 'sealed-summary', 5))->toBe('not-kept', $which)
            ->and(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('none', $which)
            ->and($store->forget(aStackAsTheStoreNamesIt())->howMany())->toBe(0, $which)
            ->and($store->forgetOlderThan(secondsAfterTheReading(100))->howMany())->toBe(0, $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('forgets a reading it cannot read when asked to forget that stack', function (): void {
    $database = AKeptDatabase::migrated();
    aRowALaterBuildWroteIn($database);

    $stores = [
        'the adapter' => new HealthReadingsInTheDatabase($database),
        'the fake' => HealthReadingsInMemory::empty()->holdsOneALaterBuildWrote(aStackAsTheStoreNamesIt()),
    ];

    foreach ($stores as $which => $store) {
        expect($store->forget(aStackAsTheStoreNamesIt())->howMany())->toBe(1, $which)
            ->and(whatTheStoreFound($store->newest(aStackAsTheStoreNamesIt())))->toBe('none', $which);
    }
});
