<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Modules\Connection\Internal\KeptSettings;
use Modules\Connection\Internal\NotifyingKept;
use Modules\Connection\Internal\Store\NotifyingInTheDatabase;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\NotifyingKeptInMemory;

// The NotifyingKept contract, run against the adapter and against the fake.
//
// Every test of what reaches the phone runs over `NotifyingKeptInMemory`, so
// what the fake promises is what those tests are written against: one row a
// stack, a later one replacing the earlier, and a row a later build wrote said
// to be one this build cannot read, which a removal still forgets.

/** When a row in these tests was noted. */
const WHEN_THE_CHOICE_WAS_NOTED = 1_790_000_000;

/** The stack the rows are kept for, as the store names it. */
function aStackItsChoicesAreKeptFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-loft');
}

/** Another stack, whose row must never answer for the first's. */
function anotherStackItsChoicesAreKeptFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-shed');
}

/** @return array<string, NotifyingKept> */
function everyChoiceStoreHoldingNothing(): array
{
    return [
        'the adapter' => new NotifyingInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => NotifyingKeptInMemory::empty(),
    ];
}

/** What the store found, as one word to compare. */
function whatTheChoiceStoreFound(KeptSettings $kept): string
{
    $said = $kept->either(
        found: static fn(SealedPayload $payload, Shape $shape): Code => Code::of(sprintf('%s:%d', $payload->forTheStore(), $shape->value)),
        none: static fn(): Code => Code::of('none'),
    )->shown();

    return $said === 'none' && $kept->holdsSomething() ? 'unreadable' : $said;
}

/** Keep this for a stack, and say when it was noted down, or that it was not. */
function whenTheChoiceWasNoted(NotifyingKept $store, SealedStack $stack, string $payload): string
{
    return $store->keep($stack, SealedPayload::of($payload), Shape::One, Instant::atEpochSeconds(WHEN_THE_CHOICE_WAS_NOTED))->either(
        down: static fn(Instant $at): Code => Code::of(sprintf('down:%d', $at->epochSeconds() - WHEN_THE_CHOICE_WAS_NOTED)),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('finds nothing where it has kept nothing, and says nothing is there', function (): void {
    foreach (everyChoiceStoreHoldingNothing() as $which => $store) {
        expect(whatTheChoiceStoreFound($store->found(aStackItsChoicesAreKeptFor())))->toBe('none', $which)
            ->and($store->found(aStackItsChoicesAreKeptFor())->holdsSomething())->toBeFalse($which);
    }
});

it('hands back what it kept for a stack, the later in place of the earlier, and nothing for another', function (): void {
    foreach (everyChoiceStoreHoldingNothing() as $which => $store) {
        $noted = whenTheChoiceWasNoted($store, aStackItsChoicesAreKeptFor(), 'the-first');
        whenTheChoiceWasNoted($store, aStackItsChoicesAreKeptFor(), 'the-second');

        expect($noted)->toBe('down:0', $which)
            ->and(whatTheChoiceStoreFound($store->found(aStackItsChoicesAreKeptFor())))->toBe('the-second:1', $which)
            ->and($store->found(aStackItsChoicesAreKeptFor())->holdsSomething())->toBeTrue($which)
            ->and(whatTheChoiceStoreFound($store->found(anotherStackItsChoicesAreKeptFor())))->toBe('none', $which);
    }
});

it('forgets what it kept for one stack and nothing it kept for another, and then everything', function (): void {
    foreach (everyChoiceStoreHoldingNothing() as $which => $store) {
        whenTheChoiceWasNoted($store, aStackItsChoicesAreKeptFor(), 'the-loft');
        whenTheChoiceWasNoted($store, anotherStackItsChoicesAreKeptFor(), 'the-shed');

        expect($store->forget(aStackItsChoicesAreKeptFor())->howMany())->toBe(1, $which)
            ->and($store->forget(aStackItsChoicesAreKeptFor())->howMany())->toBe(0, $which)
            ->and(whatTheChoiceStoreFound($store->found(aStackItsChoicesAreKeptFor())))->toBe('none', $which)
            ->and(whatTheChoiceStoreFound($store->found(anotherStackItsChoicesAreKeptFor())))->toBe('the-shed:1', $which)
            ->and($store->forgetEverything()->howMany())->toBe(1, $which)
            ->and(whatTheChoiceStoreFound($store->found(anotherStackItsChoicesAreKeptFor())))->toBe('none', $which);
    }
});

/** A row for the first stack that a later build wrote, in the adapter's own table. */
function aChoiceRowALaterBuildWroteIn(ConnectionInterface $database): void
{
    $database->table('connection_notifying_kept')->insert([
        'stack_hash' => aStackItsChoicesAreKeptFor()->forTheStore(),
        'shape' => 99,
        'noted_at' => WHEN_THE_CHOICE_WAS_NOTED,
        'payload' => 'written-by-a-later-build',
    ]);
}

it('answers a row a later build wrote as one it cannot read but holds, forgets it, and keeps a new one over it', function (): void {
    $database = AKeptDatabase::migrated();
    aChoiceRowALaterBuildWroteIn($database);

    $stores = [
        'the adapter' => new NotifyingInTheDatabase($database),
        'the fake' => NotifyingKeptInMemory::empty()->holdsOneALaterBuildWrote(aStackItsChoicesAreKeptFor()),
    ];

    foreach ($stores as $which => $store) {
        expect(whatTheChoiceStoreFound($store->found(aStackItsChoicesAreKeptFor())))->toBe('unreadable', $which)
            ->and(whatTheChoiceStoreFound($store->found(anotherStackItsChoicesAreKeptFor())))->toBe('none', $which);

        whenTheChoiceWasNoted($store, aStackItsChoicesAreKeptFor(), 'written-by-this-build');

        expect(whatTheChoiceStoreFound($store->found(aStackItsChoicesAreKeptFor())))->toBe('written-by-this-build:1', $which);
    }

    aChoiceRowALaterBuildWroteIn($database = AKeptDatabase::migrated());

    expect(new NotifyingInTheDatabase($database)->forget(aStackItsChoicesAreKeptFor())->howMany())->toBe(1)
        ->and(NotifyingKeptInMemory::empty()->holdsOneALaterBuildWrote(aStackItsChoicesAreKeptFor())->forget(aStackItsChoicesAreKeptFor())->howMany())->toBe(1);
});

it('keeps nothing, finds nothing and forgets nothing where it cannot be reached', function (): void {
    $stores = [
        'the adapter' => new NotifyingInTheDatabase(AKeptDatabase::empty()),
        'the fake' => NotifyingKeptInMemory::unreachable(),
    ];

    foreach ($stores as $which => $store) {
        expect(whenTheChoiceWasNoted($store, aStackItsChoicesAreKeptFor(), 'sealed'))->toBe('not-kept', $which)
            ->and(whatTheChoiceStoreFound($store->found(aStackItsChoicesAreKeptFor())))->toBe('none', $which)
            ->and($store->forget(aStackItsChoicesAreKeptFor())->howMany())->toBe(0, $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('writes down in its own table when a stack\'s row was last kept', function (): void {
    $database = AKeptDatabase::migrated();
    $store = new NotifyingInTheDatabase($database);
    $store->keep(aStackItsChoicesAreKeptFor(), SealedPayload::of('first'), Shape::One, Instant::atEpochSeconds(WHEN_THE_CHOICE_WAS_NOTED));
    $store->keep(aStackItsChoicesAreKeptFor(), SealedPayload::of('second'), Shape::One, Instant::atEpochSeconds(WHEN_THE_CHOICE_WAS_NOTED + 60));

    expect($database->table('connection_notifying_kept')->where('stack_hash', aStackItsChoicesAreKeptFor()->forTheStore())->value('noted_at'))->toBe(WHEN_THE_CHOICE_WAS_NOTED + 60);
});
