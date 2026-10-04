<?php

declare(strict_types=1);

use Bootstrap\Composition\EveryStoreThePhoneKeeps;
use Modules\Health\Internal\HealthReadingsKept;
use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Standings;
use Modules\Kernel\Api\WorkLeftRunning;
use Modules\News\Api\Noticing;
use Modules\Services\Internal\ListingsKept;
use Modules\Updates\Internal\UpkeepReadingsKept;

// Clear saved data asks every store the composition root registers, so what
// it can never touch is whatever that set leaves out: a pairing and a session.

/**
 * The stores the bound one answers for.
 *
 * @return list<ForgetsEverythingKept>
 */
function theStoresClearingAsks(): array
{
    $every = app()->make(ForgetsEverythingKept::class);
    expect($every)->toBeInstanceOf(EveryStoreThePhoneKeeps::class);

    /** @var list<ForgetsEverythingKept> $stores */
    $stores = new ReflectionProperty(EveryStoreThePhoneKeeps::class, 'stores')->getValue($every);

    return $stores;
}

it('asks no store that holds a pairing or a session', function (): void {
    $stores = theStoresClearingAsks();

    expect($stores)->not->toBe([])
        ->and(array_values(array_filter(
            $stores,
            static fn(ForgetsEverythingKept $store): bool => $store instanceof Stacks || $store instanceof SecureStorage,
        )))->toBe([]);
});

it('asks the stores of every marker, beside the stores of readings and settings', function (): void {
    $stores = theStoresClearingAsks();

    expect(array_filter($stores, static fn(ForgetsEverythingKept $store): bool => $store instanceof Standings))->toHaveCount(1)
        ->and(array_filter($stores, static fn(ForgetsEverythingKept $store): bool => $store instanceof WorkLeftRunning))->toHaveCount(1);
});

it('asks the store of every kind of reading the phone keeps', function (): void {
    $stores = theStoresClearingAsks();

    expect(array_filter($stores, static fn(ForgetsEverythingKept $store): bool => $store instanceof HealthReadingsKept))->toHaveCount(1)
        ->and(array_filter($stores, static fn(ForgetsEverythingKept $store): bool => $store instanceof UpkeepReadingsKept))->toHaveCount(1)
        ->and(array_filter($stores, static fn(ForgetsEverythingKept $store): bool => $store instanceof ListingsKept))->toHaveCount(1);
});

it('asks what notices news, which forgets the markers it keeps and what each stack last named', function (): void {
    expect(array_filter(theStoresClearingAsks(), static fn(ForgetsEverythingKept $store): bool => $store instanceof Noticing))->toHaveCount(1);
});
