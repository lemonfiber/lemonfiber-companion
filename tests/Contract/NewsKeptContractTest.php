<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Modules\News\Internal\KeptNews;
use Modules\News\Internal\NewsKept;
use Modules\News\Internal\Store\NewsInTheDatabase;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\NewsKeptInMemory;

// The NewsKept contract, run against the adapter and against the fake.
//
// Every test of what `news` decides runs over `NewsKeptInMemory`, so what the
// fake promises is what those tests are written against: one row a stack, a
// later one replacing the earlier, and a row a later build wrote said to be one
// this build cannot read.
//
// The adapter runs over the app's own database in memory with every migration
// the phone runs run over it, so the table is the one its migration makes.

/** When a row in these tests was noted. */
const WHEN_THE_NEWS_WAS_NOTED = 1_790_000_000;

/** The stack the rows are kept for, as the store names it. */
function aStackItsNewsIsKeptFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-loft');
}

/** Another stack, whose row must never answer for the first's. */
function anotherStackItsNewsIsKeptFor(): SealedStack
{
    return SealedStack::of('a-keyed-hash-of-the-shed');
}

/** @return array<string, NewsKept> */
function everyNewsStoreHoldingNothing(): array
{
    return [
        'the adapter' => new NewsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => NewsKeptInMemory::empty(),
    ];
}

/** What the store found, as one word to compare. */
function whatTheNewsStoreFound(KeptNews $kept): string
{
    return $kept->either(
        found: static fn(SealedPayload $payload, Shape $shape): Code => Code::of(sprintf('%s:%d', $payload->forTheStore(), $shape->value)),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();
}

/** Keep this for a stack, and say when it was noted down, or that it was not. */
function whenTheNewsWasNoted(NewsKept $store, SealedStack $stack, string $payload): string
{
    return $store->keep($stack, SealedPayload::of($payload), Shape::One, Instant::atEpochSeconds(WHEN_THE_NEWS_WAS_NOTED))->either(
        down: static fn(Instant $at): Code => Code::of(sprintf('down:%d', $at->epochSeconds() - WHEN_THE_NEWS_WAS_NOTED)),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('finds nothing where it has kept nothing', function (): void {
    foreach (everyNewsStoreHoldingNothing() as $which => $store) {
        expect(whatTheNewsStoreFound($store->found(aStackItsNewsIsKeptFor())))->toBe('none', $which);
    }
});

it('hands back what it kept for a stack, the later in place of the earlier, and nothing for another', function (): void {
    foreach (everyNewsStoreHoldingNothing() as $which => $store) {
        $noted = whenTheNewsWasNoted($store, aStackItsNewsIsKeptFor(), 'the-first');
        whenTheNewsWasNoted($store, aStackItsNewsIsKeptFor(), 'the-second');

        expect($noted)->toBe('down:0', $which)
            ->and(whatTheNewsStoreFound($store->found(aStackItsNewsIsKeptFor())))->toBe('the-second:1', $which)
            ->and(whatTheNewsStoreFound($store->found(anotherStackItsNewsIsKeptFor())))->toBe('none', $which);
    }
});

it('forgets what it kept for one stack and nothing it kept for another, and then everything', function (): void {
    foreach (everyNewsStoreHoldingNothing() as $which => $store) {
        whenTheNewsWasNoted($store, aStackItsNewsIsKeptFor(), 'the-loft');
        whenTheNewsWasNoted($store, anotherStackItsNewsIsKeptFor(), 'the-shed');

        expect($store->forget(aStackItsNewsIsKeptFor())->howMany())->toBe(1, $which)
            ->and($store->forget(aStackItsNewsIsKeptFor())->howMany())->toBe(0, $which)
            ->and(whatTheNewsStoreFound($store->found(aStackItsNewsIsKeptFor())))->toBe('none', $which)
            ->and(whatTheNewsStoreFound($store->found(anotherStackItsNewsIsKeptFor())))->toBe('the-shed:1', $which)
            ->and($store->forgetEverything()->howMany())->toBe(1, $which)
            ->and(whatTheNewsStoreFound($store->found(anotherStackItsNewsIsKeptFor())))->toBe('none', $which);
    }
});

/** A row for the first stack that a later build wrote, in the adapter's own table. */
function aNewsRowALaterBuildWroteIn(ConnectionInterface $database): void
{
    $database->table('news_kept')->insert([
        'stack_hash' => aStackItsNewsIsKeptFor()->forTheStore(),
        'shape' => 99,
        'noted_at' => WHEN_THE_NEWS_WAS_NOTED,
        'payload' => 'written-by-a-later-build',
    ]);
}

it('answers a row a later build wrote as one it cannot read, and keeps a new one over it', function (): void {
    $database = AKeptDatabase::migrated();
    aNewsRowALaterBuildWroteIn($database);

    $stores = [
        'the adapter' => new NewsInTheDatabase($database),
        'the fake' => NewsKeptInMemory::empty()->holdsOneALaterBuildWrote(aStackItsNewsIsKeptFor()),
    ];

    foreach ($stores as $which => $store) {
        expect(whatTheNewsStoreFound($store->found(aStackItsNewsIsKeptFor())))->toBe('unreadable', $which)
            ->and(whatTheNewsStoreFound($store->found(anotherStackItsNewsIsKeptFor())))->toBe('none', $which);

        whenTheNewsWasNoted($store, aStackItsNewsIsKeptFor(), 'written-by-this-build');

        expect(whatTheNewsStoreFound($store->found(aStackItsNewsIsKeptFor())))->toBe('written-by-this-build:1', $which);
    }
});

it('keeps nothing, finds nothing and forgets nothing where it cannot be reached', function (): void {
    $stores = [
        'the adapter' => new NewsInTheDatabase(AKeptDatabase::empty()),
        'the fake' => NewsKeptInMemory::unreachable(),
    ];

    foreach ($stores as $which => $store) {
        expect(whenTheNewsWasNoted($store, aStackItsNewsIsKeptFor(), 'sealed'))->toBe('not-kept', $which)
            ->and(whatTheNewsStoreFound($store->found(aStackItsNewsIsKeptFor())))->toBe('none', $which)
            ->and($store->forget(aStackItsNewsIsKeptFor())->howMany())->toBe(0, $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});
