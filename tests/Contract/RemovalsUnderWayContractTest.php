<?php

declare(strict_types=1);

use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Vault\Api\PlatformStacks;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\RemovalsUnderWayInMemory;

// The RemovalsUnderWay contract, run against the platform store and the fake.

/** A stack being removed. Named for this file. */
function aStackBeingRemoved(string $seed): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

it('holds a removal from the moment it begins until it is struck off, and each stack once', function (): void {
    foreach ([
        'the platform store' => new PlatformStacks(APlatformStore::working()),
        'the fake' => RemovalsUnderWayInMemory::working(),
    ] as $which => $removals) {
        expect($removals->underWay()->isEmpty())->toBeTrue($which)
            ->and($removals->begin(aStackBeingRemoved('a')))->toBeTrue($which)
            ->and($removals->begin(aStackBeingRemoved('a')))->toBeTrue($which)
            ->and($removals->begin(aStackBeingRemoved('b')))->toBeTrue($which)
            ->and(iterator_count($removals->underWay()->getIterator()))->toBe(2, $which)
            ->and($removals->finished(aStackBeingRemoved('a')))->toBeTrue($which)
            ->and($removals->underWay()->holds(aStackBeingRemoved('a')))->toBeFalse($which)
            ->and($removals->underWay()->holds(aStackBeingRemoved('b')))->toBeTrue($which);
    }
});

it('refuses to begin where the store cannot be written, and holds nothing', function (): void {
    foreach ([
        'the platform store' => new PlatformStacks(APlatformStore::refusing()),
        'the fake' => RemovalsUnderWayInMemory::refusing(),
    ] as $which => $removals) {
        expect($removals->begin(aStackBeingRemoved('a')))->toBeFalse($which)
            ->and($removals->underWay()->isEmpty())->toBeTrue($which);
    }
});

it('reads a record it did not write as holding nothing', function (string $written): void {
    $store = APlatformStore::working()->alreadyHolding('lemonfiber.stacks', $written);

    expect(new PlatformStacks($store)->underWay()->isEmpty())->toBeTrue();
})->with(['not JSON' => ['not json'], 'another shape' => ['{"shape":2,"removing":["x"]}'], 'no list' => ['{"shape":1}'], 'blank ids' => ['{"shape":1,"removing":["", 7]}']]);
