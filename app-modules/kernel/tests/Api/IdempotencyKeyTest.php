<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\KeyIsBlank;
use Modules\Kernel\Api\Nonce;

it('carries the key the caller chose', function (): void {
    expect(IdempotencyKey::of('01J8Z3')->sent())->toBe('01J8Z3');
});

it('sends the key without the whitespace around it', function (): void {
    // Two sends of the same command must be the same string on the wire, and
    // a padded copy of a key is a different string to the server.
    expect(IdempotencyKey::of(" 01J8Z3 ")->sent())->toBe('01J8Z3');
});

it('refuses a key that is empty', function (): void {
    expect(fn(): IdempotencyKey => IdempotencyKey::of(''))->toThrow(KeyIsBlank::class);
});

it('refuses a key that is only whitespace', function (): void {
    expect(fn(): IdempotencyKey => IdempotencyKey::of("\t "))->toThrow(KeyIsBlank::class);
});

it('says what a blank key would have cost', function (): void {
    expect(fn(): IdempotencyKey => IdempotencyKey::of(''))
        ->toThrow(KeyIsBlank::class, 'nothing to recognise a retry by');
});

it('is the same key when the text is the same', function (): void {
    expect(IdempotencyKey::of('01J8Z3')->is(IdempotencyKey::of('01J8Z3')))->toBeTrue();
});

it('is a different key when the text differs', function (): void {
    expect(IdempotencyKey::of('01J8Z3')->is(IdempotencyKey::of('01J8Z4')))->toBeFalse();
});

it('is built from a nonce, which is where one should come from', function (): void {
    // `of()` is for a key the caller already holds — read back from a request
    // that was interrupted, so the retry carries the same one. A key being
    // made comes through here, so that no command decides for itself how long
    // unguessable is (B2).
    $nonce = Nonce::of('a1b2c3d4e5f60718');

    expect(IdempotencyKey::from($nonce)->sent())->toBe($nonce->shown());
});

it('is the same key when it came from the same nonce', function (): void {
    $nonce = Nonce::of('a1b2c3d4e5f60718');

    expect(IdempotencyKey::from($nonce)->is(IdempotencyKey::of($nonce->shown())))->toBeTrue();
});
