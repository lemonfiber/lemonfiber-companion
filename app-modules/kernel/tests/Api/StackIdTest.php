<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;

const A_MINTED_ID = 'a1b2c3d4e5f60718';

it('N1-R11 — is minted on this side, from a nonce', function (): void {
    // Not taken from the wire. A server that has never met this app cannot have
    // named itself to it, and two stacks that have never met each other could
    // otherwise arrive carrying the same identifier.
    expect(StackId::of(Nonce::of(A_MINTED_ID))->stored())->toBe(A_MINTED_ID);
});

it('reads back an identifier that was already minted', function (): void {
    // A second constructor rather than one taking a string, because these are
    // different acts: `of()` mints an identity for a stack being paired, and
    // this reads one back. One constructor would make minting possible by
    // accident, from anywhere, with any string.
    expect(StackId::rememberedAs(A_MINTED_ID)->stored())->toBe(A_MINTED_ID);
    expect(StackId::rememberedAs(' a1b2c3d4e5f60718 ')->stored())->toBe(A_MINTED_ID);
});

it('refuses retained state that identifies nothing', function (): void {
    // An empty identifier read back from storage is a stack that exists in the
    // record and cannot be told apart from any other. Refused where it is read
    // rather than where a reading is attributed, because by then the wrong
    // answer is already a reading on somebody's screen.
    expect(fn(): StackId => StackId::rememberedAs('   '))
        ->toThrow(StackIsUnidentified::class, 'blank identifier');
});

it('is the same stack, and is not another', function (): void {
    $mine = StackId::of(Nonce::of(A_MINTED_ID));

    expect($mine->is(StackId::rememberedAs(A_MINTED_ID)))->toBeTrue();
    expect($mine->is(StackId::rememberedAs('0f1e2d3c4b5a6978')))->toBeFalse();
});
