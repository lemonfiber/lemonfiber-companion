<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Modules\Connection\Internal\KeptSettings;
use Modules\Connection\Internal\Store\WakingInTheDatabase;
use Modules\Connection\Internal\WakingKept;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\WakingKeptInMemory;

// The WakingKept contract, run against the adapter and against the fake.
//
// Every test of waking the phone runs over `WakingKeptInMemory`, so
// what the fake promises is what those tests are written against: one row a
// stack, a later one replacing the earlier, and a row a later build wrote said
// to be one this build cannot read, which a removal still forgets.

/** When a row in these tests was noted. */
const WHEN_THE_WAKING_WAS_NOTED = 1_790_000_000;

/** The stack the rows are kept for, as the store names it. */
function aStackItsWakingIsKeptFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-loft');
}

/** Another stack, whose row must never answer for the first's. */
function anotherStackItsWakingIsKeptFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-shed');
}

/** @return array<string, WakingKept> */
function everyWakingStoreHoldingNothing(): array
{
    return [
        'the adapter' => new WakingInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => WakingKeptInMemory::empty(),
    ];
}

/** What the store found, as one word to compare. */
function whatTheWakingStoreFound(KeptSettings $kept): string
{
    $said = $kept->either(
        found: static fn(SealedPayload $payload, Shape $shape): Code => Code::of(sprintf('%s:%d', $payload->forTheStore(), $shape->value)),
        none: static fn(): Code => Code::of('none'),
    )->shown();

    return $said === 'none' && $kept->holdsSomething() ? 'unreadable' : $said;
}

/** Keep this for a stack, and say when it was noted down, or that it was not. */
function whenTheWakingWasNoted(WakingKept $store, SealedStack $stack, string $payload): string
{
    return $store->keep($stack, SealedPayload::of($payload), Shape::One, Instant::atEpochSeconds(WHEN_THE_WAKING_WAS_NOTED))->either(
        down: static fn(Instant $at): Code => Code::of(sprintf('down:%d', $at->epochSeconds() - WHEN_THE_WAKING_WAS_NOTED)),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('finds nothing where it has kept nothing, and says nothing is there', function (): void {
    foreach (everyWakingStoreHoldingNothing() as $which => $store) {
        expect(whatTheWakingStoreFound($store->found(aStackItsWakingIsKeptFor())))->toBe('none', $which)
            ->and($store->found(aStackItsWakingIsKeptFor())->holdsSomething())->toBeFalse($which);
    }
});

it('hands back what it kept for a stack, the later in place of the earlier, and nothing for another', function (): void {
    foreach (everyWakingStoreHoldingNothing() as $which => $store) {
        $noted = whenTheWakingWasNoted($store, aStackItsWakingIsKeptFor(), 'the-first');
        whenTheWakingWasNoted($store, aStackItsWakingIsKeptFor(), 'the-second');

        expect($noted)->toBe('down:0', $which)
            ->and(whatTheWakingStoreFound($store->found(aStackItsWakingIsKeptFor())))->toBe('the-second:1', $which)
            ->and($store->found(aStackItsWakingIsKeptFor())->holdsSomething())->toBeTrue($which)
            ->and(whatTheWakingStoreFound($store->found(anotherStackItsWakingIsKeptFor())))->toBe('none', $which);
    }
});

it('forgets what it kept for one stack and nothing it kept for another, and then everything', function (): void {
    foreach (everyWakingStoreHoldingNothing() as $which => $store) {
        whenTheWakingWasNoted($store, aStackItsWakingIsKeptFor(), 'the-loft');
        whenTheWakingWasNoted($store, anotherStackItsWakingIsKeptFor(), 'the-shed');

        expect($store->forget(aStackItsWakingIsKeptFor())->howMany())->toBe(1, $which)
            ->and($store->forget(aStackItsWakingIsKeptFor())->howMany())->toBe(0, $which)
            ->and(whatTheWakingStoreFound($store->found(aStackItsWakingIsKeptFor())))->toBe('none', $which)
            ->and(whatTheWakingStoreFound($store->found(anotherStackItsWakingIsKeptFor())))->toBe('the-shed:1', $which)
            ->and($store->forgetEverything()->howMany())->toBe(1, $which)
            ->and(whatTheWakingStoreFound($store->found(anotherStackItsWakingIsKeptFor())))->toBe('none', $which);
    }
});

/** A row for the first stack that a later build wrote, in the adapter's own table. */
function aWakingRowALaterBuildWroteIn(ConnectionInterface $database): void
{
    $database->table('connection_waking_kept')->insert([
        'stack_hash' => aStackItsWakingIsKeptFor()->forTheStore(),
        'shape' => 99,
        'noted_at' => WHEN_THE_WAKING_WAS_NOTED,
        'payload' => 'written-by-a-later-build',
    ]);
}

it('answers a row a later build wrote as one it cannot read but holds, forgets it, and keeps a new one over it', function (): void {
    $database = AKeptDatabase::migrated();
    aWakingRowALaterBuildWroteIn($database);

    $stores = [
        'the adapter' => new WakingInTheDatabase($database),
        'the fake' => WakingKeptInMemory::empty()->holdsOneALaterBuildWrote(aStackItsWakingIsKeptFor()),
    ];

    foreach ($stores as $which => $store) {
        expect(whatTheWakingStoreFound($store->found(aStackItsWakingIsKeptFor())))->toBe('unreadable', $which)
            ->and(whatTheWakingStoreFound($store->found(anotherStackItsWakingIsKeptFor())))->toBe('none', $which);

        whenTheWakingWasNoted($store, aStackItsWakingIsKeptFor(), 'written-by-this-build');

        expect(whatTheWakingStoreFound($store->found(aStackItsWakingIsKeptFor())))->toBe('written-by-this-build:1', $which);
    }

    aWakingRowALaterBuildWroteIn($database = AKeptDatabase::migrated());

    expect(new WakingInTheDatabase($database)->forget(aStackItsWakingIsKeptFor())->howMany())->toBe(1)
        ->and(WakingKeptInMemory::empty()->holdsOneALaterBuildWrote(aStackItsWakingIsKeptFor())->forget(aStackItsWakingIsKeptFor())->howMany())->toBe(1);
});

it('keeps nothing, finds nothing and forgets nothing where it cannot be reached', function (): void {
    $stores = [
        'the adapter' => new WakingInTheDatabase(AKeptDatabase::empty()),
        'the fake' => WakingKeptInMemory::unreachable(),
    ];

    foreach ($stores as $which => $store) {
        expect(whenTheWakingWasNoted($store, aStackItsWakingIsKeptFor(), 'sealed'))->toBe('not-kept', $which)
            ->and(whatTheWakingStoreFound($store->found(aStackItsWakingIsKeptFor())))->toBe('none', $which)
            ->and($store->forget(aStackItsWakingIsKeptFor())->howMany())->toBe(0, $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('writes down in its own table when a stack\'s row was last kept', function (): void {
    $database = AKeptDatabase::migrated();
    $store = new WakingInTheDatabase($database);
    $store->keep(aStackItsWakingIsKeptFor(), SealedPayload::of('first'), Shape::One, Instant::atEpochSeconds(WHEN_THE_WAKING_WAS_NOTED));
    $store->keep(aStackItsWakingIsKeptFor(), SealedPayload::of('second'), Shape::One, Instant::atEpochSeconds(WHEN_THE_WAKING_WAS_NOTED + 60));

    expect($database->table('connection_waking_kept')->where('stack_hash', aStackItsWakingIsKeptFor()->forTheStore())->value('noted_at'))->toBe(WHEN_THE_WAKING_WAS_NOTED + 60);
});
