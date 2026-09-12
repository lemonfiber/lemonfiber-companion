<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\NonceIsGuessable;

use function sprintf;
use function str_repeat;

const WIDE_ENOUGH = 'a1b2c3d4e5f60718';

it('carries the value it was given', function (): void {
    expect(Nonce::of(WIDE_ENOUGH)->shown())->toBe(WIDE_ENOUGH);
});

it('is shown without the whitespace around it', function (): void {
    expect(Nonce::of(sprintf(' %s ', WIDE_ENOUGH))->shown())->toBe(WIDE_ENOUGH);
});

it('accepts a value exactly at the floor', function (): void {
    // The boundary a `<=` would get wrong, and getting it wrong here refuses
    // a nonce that is long enough rather than accepting one that is not — the
    // failure would be a command that cannot be sent at all.
    expect(Nonce::of(str_repeat('a', Nonce::SHORTEST))->shown())->toHaveLength(Nonce::SHORTEST);
});

it('refuses a value one character short', function (): void {
    expect(fn(): Nonce => Nonce::of(str_repeat('a', Nonce::SHORTEST - 1)))
        ->toThrow(NonceIsGuessable::class);
});

it('refuses an empty value', function (): void {
    expect(fn(): Nonce => Nonce::of(''))->toThrow(NonceIsGuessable::class);
});

it('counts what is left after the whitespace, not before', function (): void {
    // Padding is not width. A value that reaches the floor only because it was
    // sent with spaces around it is a short nonce that looks long.
    $padded = sprintf('   %s   ', str_repeat('a', Nonce::SHORTEST - 1));

    expect(fn(): Nonce => Nonce::of($padded))->toThrow(NonceIsGuessable::class);
});

it('says how short it was and what the floor is', function (): void {
    expect(fn(): Nonce => Nonce::of('abc'))
        ->toThrow(NonceIsGuessable::class, 'A nonce of 3 characters is short enough to search; 16 is the floor.');
});

it('is the same nonce when the value is the same', function (): void {
    expect(Nonce::of(WIDE_ENOUGH)->is(Nonce::of(WIDE_ENOUGH)))->toBeTrue();
});

it('is a different nonce when the value differs', function (): void {
    expect(Nonce::of(WIDE_ENOUGH)->is(Nonce::of(str_repeat('b', Nonce::SHORTEST))))->toBeFalse();
});
