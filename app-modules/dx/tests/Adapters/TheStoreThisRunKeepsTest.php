<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Adapters;

use function expect;
use function it;

use Modules\Dx\Adapters\TheStoreThisRunKeeps;
use Native\Mobile\SecureStorageStatus;

// The store, held to the four things the platform class promises.
//
// Its own suite rather than only the round-trips the stand-ins exercise,
// because the adapters above it use two of these methods and inherit the other
// two — and an inherited one here is not a harmless default. It is the real
// platform implementation, which reaches for a bridge that is not there and,
// on a device, would reach one that is. The overrides are what stop that, so
// each is asserted rather than assumed.

it('answers with what was written under a key', function (): void {
    $store = new TheStoreThisRunKeeps();

    expect($store->set('a-key', 'a value'))->toBeTrue()
        ->and($store->get('a-key'))->toBe('a value')
        ->and($store->read('a-key')->status)->toBe(SecureStorageStatus::Found);
});

it('tells an empty key apart from a key nobody wrote', function (): void {
    // `NotFound` and never `Unavailable` or `Failed`: this store is always
    // reachable, and reporting otherwise would put a screen in front of
    // somebody for a condition that cannot arise here.
    $store = new TheStoreThisRunKeeps();

    expect($store->read('never-written')->status)->toBe(SecureStorageStatus::NotFound)
        ->and($store->get('never-written'))->toBeNull();
});

it('forgets a key it is asked to forget', function (): void {
    $store = new TheStoreThisRunKeeps();
    $store->set('a-key', 'a value');

    expect($store->delete('a-key'))->toBeTrue()
        ->and($store->read('a-key')->status)->toBe(SecureStorageStatus::NotFound);
});

it('reads a null value as a request to forget', function (): void {
    // What the platform class does with one, and the reason this matters more
    // than it looks: `PlatformKeychain::forget()` writes null rather than
    // calling delete, so a store that kept the null would answer `Found` with
    // nothing in it — a session that is there and is not.
    $store = new TheStoreThisRunKeeps();
    $store->set('a-key', 'a value');

    expect($store->set('a-key', null))->toBeTrue()
        ->and($store->read('a-key')->status)->toBe(SecureStorageStatus::NotFound);
});

it('keeps one key apart from another', function (): void {
    $store = new TheStoreThisRunKeeps();
    $store->set('one', 'first');
    $store->set('two', 'second');

    expect($store->get('one'))->toBe('first')
        ->and($store->get('two'))->toBe('second');
});
