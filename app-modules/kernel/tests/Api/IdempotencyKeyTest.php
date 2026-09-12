<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function mb_strlen;

use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\KeyIsBlank;
use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\Nonce;

use function serialize;
use function sprintf;
use function unserialize;

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

it('N1-R42 — a key cannot be written down', function (): void {
    // The second half of the requirement, and the one a type can hold. A key
    // serves retry *within a single attempt*; a serialised key is one that
    // outlived its attempt, and whatever reads it back sends the operator's
    // earlier action again — at a moment nobody chose, against a stack whose
    // state has moved on.
    //
    // Not a redaction, the way `Session` is. The key is not a secret: it goes
    // on the wire in a header and anybody watching the connection has it. What
    // it must not do is persist.
    expect(fn(): string => serialize(IdempotencyKey::of('01J8Z3')))
        ->toThrow(MustNotLeaveThisProcess::class);
});

it('N1-R42 — a key cannot be read back either', function (): void {
    // The other half of the same door. Without it a crafted payload naming this
    // class walks back into an object carrying whatever key it liked — and a
    // key somebody else chose is a key that matches an action the operator
    // never took.
    // Built from the class name rather than typed as a literal, for the reason
    // `SessionTest` gives: a renamed class leaves a hand-written payload naming
    // a type that no longer exists, and `unserialize` answers `false` quietly
    // while the test goes on passing for the wrong reason.
    $payload = sprintf('O:%d:"%s":0:{}', mb_strlen(IdempotencyKey::class), IdempotencyKey::class);

    expect(fn(): mixed => unserialize($payload))
        ->toThrow(MustNotLeaveThisProcess::class);
});
