<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;

const A_MINTED_ID = 'a1b2c3d4e5f60718';

it('mints one on this side, for a machine no material described', function (): void {
    expect(StackId::of(Nonce::of(A_MINTED_ID))->stored())->toBe(A_MINTED_ID);
});

it('takes the identity a stack gave itself in its own material', function (): void {
    expect(StackId::saidBy(A_MINTED_ID)->stored())->toBe(A_MINTED_ID)
        ->and(StackId::saidBy(' a1b2c3d4e5f60718 ')->stored())->toBe(A_MINTED_ID);
});

it('refuses material that names its stack with nothing, and says it was the material', function (): void {
    // Told apart from a blank retained entry: this one is the stack's to
    // reissue, and the other is this device's state gone wrong.
    expect(fn(): StackId => StackId::saidBy('   '))
        ->toThrow(StackIsUnidentified::class, 'Pairing material named its stack with a blank identifier');
});

it('reads back an identifier that was already held', function (): void {
    // Its own constructor, because these are different acts: `of()` mints an
    // identity, `saidBy()` takes a stack's own, and this reads one back. One
    // constructor taking a string would make the first two possible by
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
        ->toThrow(StackIsUnidentified::class, 'Retained state named a stack with a blank identifier');
});

it('is the same stack, and is not another', function (): void {
    $mine = StackId::of(Nonce::of(A_MINTED_ID));

    expect($mine->is(StackId::rememberedAs(A_MINTED_ID)))->toBeTrue();
    expect($mine->is(StackId::rememberedAs('0f1e2d3c4b5a6978')))->toBeFalse();
});
