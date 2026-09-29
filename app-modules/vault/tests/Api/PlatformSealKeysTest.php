<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Native\Storage;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\KeyHeld;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\SealKey;
use Modules\Kernel\Api\WhyNothingIsSealed;
use Modules\Vault\Api\PlatformSealKeys;
use Native\Mobile\Testing\FakeBridge;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\APlatformStore;

// What the adapter decides that the contract cannot see: the name each key is
// kept under, the form it is written in, the moment it may be read again, and
// what becomes of a value under one of those names that is not a key.

/** A key as a seal would hand one in. */
const A_KEY_TO_KEEP = 'a-key-for-the-platform-store-too';

/** The same key as the store holds it, written out rather than encoded here. */
const A_KEY_TO_KEEP_AS_HEX = '612d6b65792d666f722d7468652d706c6174666f726d2d73746f72652d746f6f';

/** Which arm answered, and with which key, as a word. */
function whatTheStoreGaveBack(KeyHeld $held): string
{
    return $held->either(
        held: static fn(KeyMaterial $key): Code => Code::of(sprintf('held:%s', $key->bytes())),
        madeAfresh: static fn(KeyMaterial $key): Code => Code::of(sprintf('made:%s', $key->bytes())),
        refused: static fn(WhyNothingIsSealed $why): Code => Code::of(sprintf('refused:%s', $why->name)),
    )->shown();
}

it('keeps each key under its own name, prefixed so nothing else in the store collides', function (): void {
    $store = APlatformStore::working();
    $keys = new PlatformSealKeys($store);

    $keys->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(A_KEY_TO_KEEP));
    $keys->readOrKeep(SealKey::TheStackKey, KeyMaterial::of(A_KEY_TO_KEEP));

    expect($store->keysHeld())->toBe(['lemonfiber.seal.data', 'lemonfiber.seal.stack']);
});

it('writes a key as lowercase hex, and reads that back as the same key', function (): void {
    $store = APlatformStore::working();

    new PlatformSealKeys($store)->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(A_KEY_TO_KEEP));

    expect($store->whatIsUnder('lemonfiber.seal.data'))->toBe(A_KEY_TO_KEEP_AS_HEX)
        ->and(whatTheStoreGaveBack(new PlatformSealKeys($store)->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(str_repeat('x', 32)))))
        ->toBe(sprintf('held:%s', A_KEY_TO_KEEP));
});

it('replaces a value under a key\'s name that is not a key, as a key that has gone', function (): void {
    // Empty, short, long, not hex, and hex in capitals, which this adapter
    // never writes. Each is a key that cannot be read, and each is made afresh.
    foreach (['', str_repeat('a', 62), str_repeat('a', 66), str_repeat('z', 64), str_repeat('A', 64)] as $written) {
        $store = APlatformStore::working()->alreadyHolding('lemonfiber.seal.data', $written);

        expect(whatTheStoreGaveBack(new PlatformSealKeys($store)->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(A_KEY_TO_KEEP))))
            ->toBe(sprintf('made:%s', A_KEY_TO_KEEP), sprintf('[%s]', $written))
            ->and($store->whatIsUnder('lemonfiber.seal.data'))->toBe(A_KEY_TO_KEEP_AS_HEX);
    }
});

it('asks for a key to be readable only while the device is unlocked', function (): void {
    FakeBridge::disable();
    $bridge = FakeBridge::enable()
        ->respondTo('Lemonfiber.Storage.Read', ['outcome' => 'nothing'])
        ->respondTo('Lemonfiber.Storage.Keep', ['outcome' => 'kept', 'readable' => 'while_unlocked']);

    new PlatformSealKeys(new Storage())->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(A_KEY_TO_KEEP));

    $bridge->assertCalled(
        'Lemonfiber.Storage.Keep',
        static fn(array $sent): bool => $sent['key'] === 'lemonfiber.seal.data'
            && $sent['value'] === A_KEY_TO_KEEP_AS_HEX
            && $sent['readable'] === 'while_unlocked',
    );

    FakeBridge::disable();
});

it('refuses rather than making a key where the store read nothing and then would not keep one', function (): void {
    FakeBridge::disable();
    FakeBridge::enable()
        ->respondTo('Lemonfiber.Storage.Read', ['outcome' => 'nothing'])
        ->respondTo('Lemonfiber.Storage.Keep', ['outcome' => 'refused', 'because' => 'store_would_not_open']);

    expect(whatTheStoreGaveBack(new PlatformSealKeys(new Storage())->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(A_KEY_TO_KEEP))))
        ->toBe('refused:KeyUnreadable');

    FakeBridge::disable();
});
