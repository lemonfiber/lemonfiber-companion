<?php

declare(strict_types=1);

use Modules\Health\Internal\MissingAlertsKept;
use Modules\Health\Internal\Store\MissingAlertsInTheDatabase;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedStack;
use Tests\Support\AKeptDatabase;
use Tests\Support\Fakes\MissingAlertsInMemory;

// The MissingAlertsKept contract, run against the adapter and against the fake.

/** When a stack was first marked, in epoch seconds. */
const WHEN_A_STACK_WAS_MARKED = 1_790_000_000;

/** The table health keeps its marks in. Named for this file (`G10`). */
const THE_MISSING_ALERTS_TABLE = 'health_alerts_may_be_missing';

/**
 * Each store of marks, holding nothing yet.
 *
 * @return array<string, MissingAlertsKept>
 */
function everyStoreOfMarksHoldingNothing(): array
{
    return [
        'the adapter' => new MissingAlertsInTheDatabase(AKeptDatabase::migrated()),
        'the fake' => MissingAlertsInMemory::empty(),
    ];
}

/** When a mark was made, or `not-kept`. */
function whenItWasMarked(Noted $noted): string
{
    return $noted->either(
        down: static fn(Instant $at): Code => Code::of((string) $at->epochSeconds()),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('holds no mark for a stack it has marked nothing for', function (): void {
    foreach (everyStoreOfMarksHoldingNothing() as $which => $store) {
        expect(whenItWasMarked($store->whenMarked(SealedStack::of('the-loft'))))->toBe('not-kept', $which)
            ->and($store->forget(SealedStack::of('the-loft'))->howMany())->toBe(0, $which);
    }
});

it('holds one mark a stack however often it is marked, as of the latest, and none for another', function (): void {
    foreach (everyStoreOfMarksHoldingNothing() as $which => $store) {
        $first = $store->mark(SealedStack::of('the-loft'), Instant::atEpochSeconds(WHEN_A_STACK_WAS_MARKED));
        $again = $store->mark(SealedStack::of('the-loft'), Instant::atEpochSeconds(WHEN_A_STACK_WAS_MARKED + 60));

        expect(whenItWasMarked($first))->toBe((string) WHEN_A_STACK_WAS_MARKED, $which)
            ->and(whenItWasMarked($again))->toBe((string) (WHEN_A_STACK_WAS_MARKED + 60), $which)
            ->and(whenItWasMarked($store->whenMarked(SealedStack::of('the-loft'))))->toBe((string) (WHEN_A_STACK_WAS_MARKED + 60), $which)
            ->and(whenItWasMarked($store->whenMarked(SealedStack::of('the-shed'))))->toBe('not-kept', $which);
    }
});

it('forgets one stack\'s mark and no other\'s, and then every mark', function (): void {
    foreach (everyStoreOfMarksHoldingNothing() as $which => $store) {
        $store->mark(SealedStack::of('the-loft'), Instant::atEpochSeconds(WHEN_A_STACK_WAS_MARKED));
        $store->mark(SealedStack::of('the-shed'), Instant::atEpochSeconds(WHEN_A_STACK_WAS_MARKED));
        $store->mark(SealedStack::of('the-barn'), Instant::atEpochSeconds(WHEN_A_STACK_WAS_MARKED));

        expect($store->forget(SealedStack::of('the-loft'))->howMany())->toBe(1, $which)
            ->and(whenItWasMarked($store->whenMarked(SealedStack::of('the-loft'))))->toBe('not-kept', $which)
            ->and(whenItWasMarked($store->whenMarked(SealedStack::of('the-shed'))))->toBe((string) WHEN_A_STACK_WAS_MARKED, $which)
            ->and($store->forget(SealedStack::of('the-loft'))->howMany())->toBe(0, $which)
            ->and($store->forgetEverything()->howMany())->toBe(2, $which)
            ->and(whenItWasMarked($store->whenMarked(SealedStack::of('the-shed'))))->toBe('not-kept', $which);
    }
});

it('keeps nothing, holds nothing and forgets nothing where it cannot be reached', function (): void {
    $stores = [
        'the adapter' => new MissingAlertsInTheDatabase(AKeptDatabase::empty()),
        'the fake' => MissingAlertsInMemory::unreachable(),
    ];

    foreach ($stores as $which => $store) {
        expect(whenItWasMarked($store->mark(SealedStack::of('the-loft'), Instant::atEpochSeconds(WHEN_A_STACK_WAS_MARKED))))->toBe('not-kept', $which)
            ->and(whenItWasMarked($store->whenMarked(SealedStack::of('the-loft'))))->toBe('not-kept', $which)
            ->and($store->forget(SealedStack::of('the-loft'))->howMany())->toBe(0, $which)
            ->and($store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('writes down in its own table when a stack was last marked', function (): void {
    $database = AKeptDatabase::migrated();
    $store = new MissingAlertsInTheDatabase($database);
    $store->mark(SealedStack::of('the-loft'), Instant::atEpochSeconds(WHEN_A_STACK_WAS_MARKED));
    $store->mark(SealedStack::of('the-loft'), Instant::atEpochSeconds(WHEN_A_STACK_WAS_MARKED + 60));

    expect($database->table(THE_MISSING_ALERTS_TABLE)->where('stack_hash', SealedStack::of('the-loft')->forTheStore())->value('noted_at'))->toBe(WHEN_A_STACK_WAS_MARKED + 60);
});
