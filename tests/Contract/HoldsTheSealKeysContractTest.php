<?php

declare(strict_types=1);

use Lemonfiber\Native\Storage;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HoldsTheSealKeys;
use Modules\Kernel\Api\KeyHeld;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\SealKey;
use Modules\Kernel\Api\WhyNothingIsSealed;
use Modules\Vault\Api\PlatformSealKeys;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\SealKeysInMemory;

// The HoldsTheSealKeys contract, run against the adapter and against the fake.
//
// Every test of the seal that does not run over the platform's store runs over
// `SealKeysInMemory`, so the promises here are the ones those tests stand on: a
// key is made once and handed back after, the two keys never answer for each
// other, and the two ways of having no key are told apart. A fake that made a
// new key on a store that would not open would make every one of those tests
// green about a phone that throws away what it kept each time it is locked.

/** A key made on a first launch, as a seal would hand it in. */
const THE_FIRST_KEY_MADE = 'the-first-key-made-for-this-test';

/** The first key as the platform's store is sent it, written out rather than encoded here. */
const THE_FIRST_KEY_MADE_AS_HEX = '7468652d66697273742d6b65792d6d6164652d666f722d746869732d74657374';

/** A second key, handed in on a later ask, which must not replace the first. */
const A_LATER_KEY_OFFERED = 'a-later-key-that-must-not-be-kep';

/**
 * Each key store on a phone whose secure storage works and holds no key yet.
 *
 * @return array<string, HoldsTheSealKeys>
 */
function everyKeyStoreOnAFirstLaunch(): array
{
    return [
        'the adapter' => new PlatformSealKeys(APlatformStore::working()),
        'the fake' => SealKeysInMemory::empty(),
    ];
}

/**
 * Each key store on a phone where no key can be had, for one reason.
 *
 * @return array<string, HoldsTheSealKeys>
 */
function everyKeyStoreThatCannotBeHad(WhyNothingIsSealed $why): array
{
    return match ($why) {
        WhyNothingIsSealed::NoSecureStorage => [
            'the adapter' => new PlatformSealKeys(APlatformStore::absent()),
            'the fake' => SealKeysInMemory::withNoSecureStorage(),
        ],
        WhyNothingIsSealed::KeyUnreadable => [
            'the adapter' => new PlatformSealKeys(APlatformStore::refusing()),
            'the fake' => SealKeysInMemory::thatWillNotOpen(),
        ],
    };
}

/** Which arm answered and with which key, as a word. */
function whichKeyCameBack(KeyHeld $held): string
{
    return $held->either(
        held: static fn(KeyMaterial $key): Code => Code::of(sprintf('held:%s', $key->bytes())),
        madeAfresh: static fn(KeyMaterial $key): Code => Code::of(sprintf('made:%s', $key->bytes())),
        refused: static fn(WhyNothingIsSealed $why): Code => Code::of(sprintf('refused:%s', $why->name)),
    )->shown();
}

it('keeps the key it is handed where there is none, and hands that one back after', function (): void {
    foreach (everyKeyStoreOnAFirstLaunch() as $which => $keys) {
        expect(whichKeyCameBack($keys->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(THE_FIRST_KEY_MADE))))
            ->toBe(sprintf('made:%s', THE_FIRST_KEY_MADE), $which)
            ->and(whichKeyCameBack($keys->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(A_LATER_KEY_OFFERED))))
            ->toBe(sprintf('held:%s', THE_FIRST_KEY_MADE), $which);
    }
});

it('keeps the data key and the stack key apart', function (): void {
    // One key answering for both would make the hash that finds a stack's
    // rows a hash under the key that opens them.
    foreach (everyKeyStoreOnAFirstLaunch() as $which => $keys) {
        $keys->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(THE_FIRST_KEY_MADE));

        expect(whichKeyCameBack($keys->readOrKeep(SealKey::TheStackKey, KeyMaterial::of(A_LATER_KEY_OFFERED))))
            ->toBe(sprintf('made:%s', A_LATER_KEY_OFFERED), $which)
            ->and(whichKeyCameBack($keys->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(A_LATER_KEY_OFFERED))))
            ->toBe(sprintf('held:%s', THE_FIRST_KEY_MADE), $which);
    }
});

it('refuses where there is no secure storage, and says so', function (): void {
    foreach (everyKeyStoreThatCannotBeHad(WhyNothingIsSealed::NoSecureStorage) as $which => $keys) {
        foreach (SealKey::cases() as $key) {
            expect(whichKeyCameBack($keys->readOrKeep($key, KeyMaterial::of(THE_FIRST_KEY_MADE))))
                ->toBe('refused:NoSecureStorage', sprintf('%s, %s', $which, $key->name));
        }
    }
});

it('refuses where the store will not open rather than making a key in its place', function (): void {
    // The difference between the two refusals is what an owner does next: a
    // phone with no storage keeps nothing, and a store that will not open may
    // open next time with what it sealed still readable.
    foreach (everyKeyStoreThatCannotBeHad(WhyNothingIsSealed::KeyUnreadable) as $which => $keys) {
        foreach (SealKey::cases() as $key) {
            expect(whichKeyCameBack($keys->readOrKeep($key, KeyMaterial::of(THE_FIRST_KEY_MADE))))
                ->toBe('refused:KeyUnreadable', sprintf('%s, %s', $which, $key->name));
        }
    }
});

it('makes a key afresh where the one it held has gone', function (): void {
    $store = APlatformStore::working();
    $adapter = new PlatformSealKeys($store);
    $fake = SealKeysInMemory::empty();

    foreach ([$adapter, $fake] as $keys) {
        $keys->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(THE_FIRST_KEY_MADE));
    }

    $store->forget('lemonfiber.seal.data');
    $fake->loses(SealKey::TheDataKey);

    foreach (['the adapter' => $adapter, 'the fake' => $fake] as $which => $keys) {
        expect(whichKeyCameBack($keys->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(A_LATER_KEY_OFFERED))))
            ->toBe(sprintf('made:%s', A_LATER_KEY_OFFERED), $which);
    }
});

// Beyond the contract: what the adapter asks the device's store for, over
// `nativephp/mobile`'s own bridge double, so the real call is what runs.

it('PlatformSealKeys asks for a key to be readable only while the device is unlocked', function (): void {
    FakeBridge::disable();
    $bridge = FakeBridge::enable()
        ->respondTo('Lemonfiber.Storage.Read', ['outcome' => 'nothing'])
        ->respondTo('Lemonfiber.Storage.Keep', ['outcome' => 'kept', 'readable' => 'while_unlocked']);

    new PlatformSealKeys(new Storage())->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(THE_FIRST_KEY_MADE));

    $bridge->assertCalled(
        'Lemonfiber.Storage.Keep',
        static fn(array $sent): bool => $sent['key'] === 'lemonfiber.seal.data'
            && $sent['value'] === THE_FIRST_KEY_MADE_AS_HEX
            && $sent['readable'] === 'while_unlocked',
    );

    FakeBridge::disable();
});

it('PlatformSealKeys refuses rather than making a key where the store read nothing and then would not keep one', function (): void {
    FakeBridge::disable();
    FakeBridge::enable()
        ->respondTo('Lemonfiber.Storage.Read', ['outcome' => 'nothing'])
        ->respondTo('Lemonfiber.Storage.Keep', ['outcome' => 'refused', 'because' => 'store_would_not_open']);

    expect(whichKeyCameBack(new PlatformSealKeys(new Storage())->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(THE_FIRST_KEY_MADE))))
        ->toBe('refused:KeyUnreadable');

    FakeBridge::disable();
});
