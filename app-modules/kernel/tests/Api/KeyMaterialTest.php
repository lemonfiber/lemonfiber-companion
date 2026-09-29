<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function json_encode;
use function mb_strlen;

use Modules\Kernel\Api\KeyIsTheWrongLength;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\MustNotLeaveThisProcess;

use function print_r;
use function serialize;
use function sprintf;
use function str_contains;
use function str_repeat;
use function unserialize;

/** Thirty-two bytes that are plainly not a key anybody uses. */
const THIRTY_TWO_BYTES = 'not-a-key-but-thirty-two-bytes!!';

it('carries the bytes it was built from, unchanged', function (): void {
    expect(KeyMaterial::of(THIRTY_TWO_BYTES)->bytes())->toBe(THIRTY_TWO_BYTES);
});

it('is thirty-two bytes, which is the cipher\'s key length', function (): void {
    expect(KeyMaterial::BYTES)->toBe(32)
        ->and(mb_strlen(THIRTY_TWO_BYTES, '8bit'))->toBe(KeyMaterial::BYTES);
});

it('refuses a byte short and a byte over, naming the length and the only one there is', function (): void {
    expect(fn(): KeyMaterial => KeyMaterial::of(str_repeat('k', 31)))
        ->toThrow(KeyIsTheWrongLength::class, 'A key of 31 bytes is not a key; 32 is the only length there is.');
    expect(fn(): KeyMaterial => KeyMaterial::of(str_repeat('k', 33)))
        ->toThrow(KeyIsTheWrongLength::class, 'A key of 33 bytes is not a key; 32 is the only length there is.');
});

it('counts bytes rather than characters', function (): void {
    // Sixteen two-byte characters are thirty-two bytes, which is a key; read
    // as characters they are sixteen, which is not.
    expect(KeyMaterial::of(str_repeat('é', 16))->bytes())->toBe(str_repeat('é', 16));
    expect(fn(): KeyMaterial => KeyMaterial::of(str_repeat('é', 32)))
        ->toThrow(KeyIsTheWrongLength::class, 'A key of 64 bytes');
});

it('does not print itself into a debugger or a payload', function (): void {
    $key = KeyMaterial::of(THIRTY_TWO_BYTES);

    expect($key->__debugInfo())->toBe(['bytes' => '(a key, hidden)'])
        ->and(json_encode(['key' => $key]))->toBe('{"key":"(a key, hidden)"}')
        ->and(str_contains(print_r($key, return: true), THIRTY_TWO_BYTES))->toBeFalse();
});

it('does not leave the process in a serialised payload', function (): void {
    expect(fn(): string => serialize(KeyMaterial::of(THIRTY_TWO_BYTES)))
        ->toThrow(MustNotLeaveThisProcess::class, 'A seal key may not be serialised.');
});

it('does not come back from a serialised payload either', function (): void {
    $payload = sprintf('O:%d:"%s":0:{}', mb_strlen(KeyMaterial::class), KeyMaterial::class);

    expect(fn(): mixed => unserialize($payload))
        ->toThrow(MustNotLeaveThisProcess::class, 'A seal key may not be serialised.');
});
