<?php

declare(strict_types=1);

namespace Modules\Seal\Tests\Api;

use function expect;
use function hash_hmac;

use Illuminate\Encryption\Encrypter;

use function it;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Sealing;
use Modules\Kernel\Api\SealKey;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Unsealing;
use Modules\Seal\Api\EncrypterSeal;

use function serialize;
use function sodium_base642bin;

use const SODIUM_BASE64_VARIANT_ORIGINAL;

use function sodium_bin2base64;
use function sprintf;

use Tests\Support\Fakes\SealKeysInMemory;
use Tests\Support\Fakes\SequencedEntropy;

// What the adapter decides that the contract cannot see: which cipher, which
// key for which job, and that nothing it opens is unserialised. The contract
// holds it and the fake to the same promises; this holds it to the
// arrangement those promises rest on.

/** The data key these tests hold, as the platform's store would hand it back. */
const THE_DATA_KEY = 'the-data-key-held-for-this-test!';

/** The stack key, which must never be the one a payload is sealed under. */
const THE_STACK_KEY = 'the-stack-key-held-for-this-test';

/** The stack these tests hash. */
const A_STACK_TO_HASH = 'a1b2c3d4e5f60718';

/** Where a payload's sealed value begins, inside the frame the encrypter writes. */
const WHERE_THE_VALUE_BEGINS = '"value":"';

function aSealHoldingBothKeys(): EncrypterSeal
{
    return new EncrypterSeal(
        SealKeysInMemory::holding(KeyMaterial::of(THE_DATA_KEY), KeyMaterial::of(THE_STACK_KEY)),
        SequencedEntropy::counting(),
    );
}

/**
 * A value sealed by the cipher itself, with no seal in between.
 *
 * A function rather than a call in a test body: the encrypter raises checked
 * exceptions, and the analyser refuses one raised inside a closure.
 */
function sealedByTheCipher(string $key, string $value): SealedPayload
{
    return SealedPayload::of(new Encrypter($key, 'aes-256-gcm')->encryptString($value));
}

/** A payload opened by the cipher itself, for the same reason as the one above. */
function openedByTheCipher(string $key, SealedPayload $payload): string
{
    return new Encrypter($key, 'aes-256-gcm')->decryptString($payload->forTheStore());
}

/**
 * The same payload with the first character of its sealed value changed.
 *
 * Inside the ciphertext rather than anywhere in the frame, so that what is
 * refused is an altered value and not a frame that no longer parses.
 */
function withTheSealedValueAltered(SealedPayload $payload): SealedPayload
{
    $frame = sodium_base642bin($payload->forTheStore(), SODIUM_BASE64_VARIANT_ORIGINAL);
    $at = mb_strpos($frame, WHERE_THE_VALUE_BEGINS);

    if ($at === false) {
        return SealedPayload::of('');
    }

    $at += mb_strlen(WHERE_THE_VALUE_BEGINS);
    $swapped = mb_substr($frame, $at, 1) === 'A' ? 'B' : 'A';

    return SealedPayload::of(sodium_bin2base64(
        sprintf('%s%s%s', mb_substr($frame, 0, $at), $swapped, mb_substr($frame, $at + 1)),
        SODIUM_BASE64_VARIANT_ORIGINAL,
    ));
}

/** The payload a sealing answered with, or one that is not a payload. */
function sealedInto(Sealing $sealing): SealedPayload
{
    return $sealing->either(
        sealed: static fn(SealedPayload $payload): SealedPayload => $payload,
        refused: static fn(): SealedPayload => SealedPayload::of(''),
    );
}

/** What an opening came to, as a word. */
function cameTo(Unsealing $opening): string
{
    return $opening->either(
        opened: static fn(Unsealed $value): Code => Code::of(sprintf('[%s]', $value->inTheClear())),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();
}

it('seals with AES-256-GCM under the data key', function (): void {
    $payload = sealedInto(aSealHoldingBothKeys()->seal(Unsealed::of('what was kept')));

    expect(openedByTheCipher(THE_DATA_KEY, $payload))->toBe('what was kept');
});

it('opens what the same cipher sealed under the data key', function (): void {
    expect(cameTo(aSealHoldingBothKeys()->open(sealedByTheCipher(THE_DATA_KEY, 'what was kept'))))
        ->toBe('[what was kept]');
});

it('does not open what was sealed under the stack key', function (): void {
    expect(cameTo(aSealHoldingBothKeys()->open(sealedByTheCipher(THE_STACK_KEY, 'what was kept'))))
        ->toBe('unreadable');
});

it('hands back a serialised object as the string it is, never as the object', function (): void {
    // Strings in and strings out. A seal that unserialised what it opened
    // would build whatever object a payload named.
    $seal = aSealHoldingBothKeys();
    $written = serialize(Code::of('an-object-in-a-payload'));

    expect(cameTo($seal->open(sealedInto($seal->seal(Unsealed::of($written))))))
        ->toBe(sprintf('[%s]', $written));
});

it('does not open a payload whose sealed value was altered inside an intact frame', function (): void {
    $seal = aSealHoldingBothKeys();
    $payload = sealedInto($seal->seal(Unsealed::of('what was kept')));

    expect(cameTo($seal->open($payload)))->toBe('[what was kept]')
        ->and(cameTo($seal->open(withTheSealedValueAltered($payload))))->toBe('unreadable');
});

it('names a stack by the HMAC-SHA256 of its identity under the stack key', function (): void {
    expect(aSealHoldingBothKeys()->stack(StackId::of(Nonce::of(A_STACK_TO_HASH)))->forTheStore())
        ->toBe(hash_hmac('sha256', A_STACK_TO_HASH, THE_STACK_KEY));
});

it('does not name a stack under the data key', function (): void {
    expect(aSealHoldingBothKeys()->stack(StackId::of(Nonce::of(A_STACK_TO_HASH)))->forTheStore())
        ->not->toBe(hash_hmac('sha256', A_STACK_TO_HASH, THE_DATA_KEY));
});

it('keeps the data key it made through the port, and seals under the one it kept', function (): void {
    $keys = SealKeysInMemory::empty();
    $payload = sealedInto(new EncrypterSeal($keys, SequencedEntropy::counting())->seal(Unsealed::of('what was kept')));

    $kept = $keys->readOrKeep(SealKey::TheDataKey, KeyMaterial::of(THE_STACK_KEY))->either(
        held: static fn(KeyMaterial $key): KeyMaterial => $key,
        madeAfresh: static fn(): KeyMaterial => KeyMaterial::of(THE_STACK_KEY),
        refused: static fn(): KeyMaterial => KeyMaterial::of(THE_STACK_KEY),
    );

    expect(openedByTheCipher($kept->bytes(), $payload))->toBe('what was kept');
});

it('keeps the stack key it made through the port, and hashes under the one it kept', function (): void {
    $keys = SealKeysInMemory::empty();
    $hash = new EncrypterSeal($keys, SequencedEntropy::counting())->stack(StackId::of(Nonce::of(A_STACK_TO_HASH)))->forTheStore();

    $kept = $keys->readOrKeep(SealKey::TheStackKey, KeyMaterial::of(THE_DATA_KEY))->either(
        held: static fn(KeyMaterial $key): KeyMaterial => $key,
        madeAfresh: static fn(): KeyMaterial => KeyMaterial::of(THE_DATA_KEY),
        refused: static fn(): KeyMaterial => KeyMaterial::of(THE_DATA_KEY),
    );

    expect($hash)->toBe(hash_hmac('sha256', A_STACK_TO_HASH, $kept->bytes()));
});

it('opens nothing under a key it had to make just now', function (): void {
    // A key made on this call has sealed nothing yet, whatever it is handed.
    expect(cameTo(new EncrypterSeal(SealKeysInMemory::empty(), SequencedEntropy::counting())
        ->open(sealedByTheCipher(THE_DATA_KEY, 'what was kept'))))
        ->toBe('unreadable');
});
