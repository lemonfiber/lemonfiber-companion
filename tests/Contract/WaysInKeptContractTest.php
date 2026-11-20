<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Modules\Connection\Internal\KeptSettings;
use Modules\Connection\Internal\Store\WaysInInTheDatabase;
use Modules\Connection\Internal\WaysInKept;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\WaysInKeptInMemory;

// The WaysInKept contract, run against the adapter and against the fake.
//
// Every test of a sign-in that leads with a way runs over `WaysInKeptInMemory`, so
// what the fake promises is what those tests are written against: one row a
// stack, a later one replacing the earlier, and a row a later build wrote said
// to be one this build cannot read, which a removal still forgets.

/** When a row in these tests was noted. */
const WHEN_THE_WAY_IN_WAS_NOTED = 1_790_000_000;

/** The stack the rows are kept for, as the store names it. */
function aStackItsWayInIsKeptFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-loft');
}

/** Another stack, whose row must never answer for the first's. */
function anotherStackItsWayInIsKeptFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-shed');
}

/** @return array<string, WaysInKept> */
function everyWayInStoreHoldingNothing(): array
{
    return [
        'the adapter' => new WaysInInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => WaysInKeptInMemory::empty(),
    ];
}

/** What the store found, as one word to compare. */
function whatTheWayInStoreFound(KeptSettings $kept): string
{
    $said = $kept->either(
        found: static fn(SealedPayload $payload, Shape $shape): Code => Code::of(sprintf('%s:%d', $payload->forTheStore(), $shape->value)),
        none: static fn(): Code => Code::of('none'),
    )->shown();

    return $said === 'none' && $kept->holdsSomething() ? 'unreadable' : $said;
}

/** Keep this for a stack, and say when it was noted down, or that it was not. */
function whenTheWayInWasNoted(WaysInKept $store, SealedStack $stack, string $payload): string
{
    return $store->keep($stack, SealedPayload::of($payload), Shape::One, Instant::atEpochSeconds(WHEN_THE_WAY_IN_WAS_NOTED))->either(
        down: static fn(Instant $at): Code => Code::of(sprintf('down:%d', $at->epochSeconds() - WHEN_THE_WAY_IN_WAS_NOTED)),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('finds nothing where it has kept nothing, and says nothing is there', function (): void {
    foreach (everyWayInStoreHoldingNothing() as $which => $store) {
        expect(whatTheWayInStoreFound($store->found(aStackItsWayInIsKeptFor())))->toBe('none', $which)
            ->and($store->found(aStackItsWayInIsKeptFor())->holdsSomething())->toBeFalse($which);
    }
});

it('hands back what it kept for a stack, the later in place of the earlier, and nothing for another', function (): void {
    foreach (everyWayInStoreHoldingNothing() as $which => $store) {
        $noted = whenTheWayInWasNoted($store, aStackItsWayInIsKeptFor(), 'the-first');
        whenTheWayInWasNoted($store, aStackItsWayInIsKeptFor(), 'the-second');

        expect($noted)->toBe('down:0', $which)
            ->and(whatTheWayInStoreFound($store->found(aStackItsWayInIsKeptFor())))->toBe('the-second:1', $which)
            ->and($store->found(aStackItsWayInIsKeptFor())->holdsSomething())->toBeTrue($which)
            ->and(whatTheWayInStoreFound($store->found(anotherStackItsWayInIsKeptFor())))->toBe('none', $which);
    }
});

it('forgets what it kept for one stack and nothing it kept for another, and then everything', function (): void {
    foreach (everyWayInStoreHoldingNothing() as $which => $store) {
        whenTheWayInWasNoted($store, aStackItsWayInIsKeptFor(), 'the-loft');
        whenTheWayInWasNoted($store, anotherStackItsWayInIsKeptFor(), 'the-shed');

        expect($store->forget(aStackItsWayInIsKeptFor())->howMany())->toBe(1, $which)
            ->and($store->forget(aStackItsWayInIsKeptFor())->howMany())->toBe(0, $which)
            ->and(whatTheWayInStoreFound($store->found(aStackItsWayInIsKeptFor())))->toBe('none', $which)
            ->and(whatTheWayInStoreFound($store->found(anotherStackItsWayInIsKeptFor())))->toBe('the-shed:1', $which)
            ->and($store->forgetEverything()->howMany())->toBe(1, $which)
            ->and(whatTheWayInStoreFound($store->found(anotherStackItsWayInIsKeptFor())))->toBe('none', $which);
    }
});

/** A row for the first stack that a later build wrote, in the adapter's own table. */
function aWayInRowALaterBuildWroteIn(ConnectionInterface $database): void
{
    $database->table('connection_way_in_kept')->insert([
        'stack_hash' => aStackItsWayInIsKeptFor()->forTheStore(),
        'shape' => 99,
        'noted_at' => WHEN_THE_WAY_IN_WAS_NOTED,
        'payload' => 'written-by-a-later-build',
    ]);
}

it('answers a row a later build wrote as one it cannot read but holds, forgets it, and keeps a new one over it', function (): void {
    $database = AKeptDatabase::migrated();
    aWayInRowALaterBuildWroteIn($database);

    $stores = [
        'the adapter' => new WaysInInTheDatabase($database),
        'the fake' => WaysInKeptInMemory::empty()->holdsOneALaterBuildWrote(aStackItsWayInIsKeptFor()),
    ];

    foreach ($stores as $which => $store) {
        expect(whatTheWayInStoreFound($store->found(aStackItsWayInIsKeptFor())))->toBe('unreadable', $which)
            ->and(whatTheWayInStoreFound($store->found(anotherStackItsWayInIsKeptFor())))->toBe('none', $which);

        whenTheWayInWasNoted($store, aStackItsWayInIsKeptFor(), 'written-by-this-build');

        expect(whatTheWayInStoreFound($store->found(aStackItsWayInIsKeptFor())))->toBe('written-by-this-build:1', $which);
    }

    aWayInRowALaterBuildWroteIn($database = AKeptDatabase::migrated());

    expect(new WaysInInTheDatabase($database)->forget(aStackItsWayInIsKeptFor())->howMany())->toBe(1)
        ->and(WaysInKeptInMemory::empty()->holdsOneALaterBuildWrote(aStackItsWayInIsKeptFor())->forget(aStackItsWayInIsKeptFor())->howMany())->toBe(1);
});

it('keeps nothing, finds nothing and forgets nothing where it cannot be reached', function (): void {
    $stores = [
        'the adapter' => new WaysInInTheDatabase(AKeptDatabase::empty()),
        'the fake' => WaysInKeptInMemory::unreachable(),
    ];

    foreach ($stores as $which => $store) {
        expect(whenTheWayInWasNoted($store, aStackItsWayInIsKeptFor(), 'sealed'))->toBe('not-kept', $which)
            ->and(whatTheWayInStoreFound($store->found(aStackItsWayInIsKeptFor())))->toBe('none', $which)
            ->and($store->forget(aStackItsWayInIsKeptFor())->howMany())->toBe(0, $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('writes down in its own table when a stack\'s row was last kept', function (): void {
    $database = AKeptDatabase::migrated();
    $store = new WaysInInTheDatabase($database);
    $store->keep(aStackItsWayInIsKeptFor(), SealedPayload::of('first'), Shape::One, Instant::atEpochSeconds(WHEN_THE_WAY_IN_WAS_NOTED));
    $store->keep(aStackItsWayInIsKeptFor(), SealedPayload::of('second'), Shape::One, Instant::atEpochSeconds(WHEN_THE_WAY_IN_WAS_NOTED + 60));

    expect($database->table('connection_way_in_kept')->where('stack_hash', aStackItsWayInIsKeptFor()->forTheStore())->value('noted_at'))->toBe(WHEN_THE_WAY_IN_WAS_NOTED + 60);
});
