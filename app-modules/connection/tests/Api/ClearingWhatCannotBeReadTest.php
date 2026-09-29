<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;

use Modules\Connection\Api\ClearingWhatCannotBeRead;
use Modules\Connection\Api\WhatWasKeptAtOpening;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\HealthReadingsInMemory;

/** A store holding one reading, as a phone that has been used holds one. */
function aStoreHoldingAReading(): HealthReadingsInMemory
{
    $store = HealthReadingsInMemory::empty();
    $store->keep(SealedStack::of('a-keyed-hash-of-the-loft'), SealedPayload::of('sealed'), Shape::One, Instant::atEpochSeconds(1_790_000_000));

    return $store;
}

/** A seal whose keys were made on an earlier launch and are held now. */
function aSealHoldingItsKeys(): ASealInMemory
{
    $seal = ASealInMemory::working();
    $seal->standing();

    return $seal;
}

it('clears every store, and says so, where the key had gone and a new one was made', function (): void {
    $store = aStoreHoldingAReading();
    $seal = aSealHoldingItsKeys()->losesItsKeys();

    expect(new ClearingWhatCannotBeRead($seal, $store)->onOpening())->toBe(WhatWasKeptAtOpening::Cleared)
        ->and($store->forgetEverything()->howMany())->toBe(0);
});

it('says it once: the next opening finds the new key held', function (): void {
    $store = aStoreHoldingAReading();
    $clearing = new ClearingWhatCannotBeRead(aSealHoldingItsKeys()->losesItsKeys(), $store);

    expect($clearing->onOpening())->toBe(WhatWasKeptAtOpening::Cleared)
        ->and($clearing->onOpening())->toBe(WhatWasKeptAtOpening::AsItWas);
});

it('says nothing on a first launch, when the key is new and nothing was kept', function (): void {
    expect(new ClearingWhatCannotBeRead(ASealInMemory::working(), HealthReadingsInMemory::empty())->onOpening())->toBe(WhatWasKeptAtOpening::AsItWas);
});

it('leaves what was kept where the key is held', function (): void {
    $store = aStoreHoldingAReading();

    expect(new ClearingWhatCannotBeRead(aSealHoldingItsKeys(), $store)->onOpening())->toBe(WhatWasKeptAtOpening::AsItWas)
        ->and($store->forgetEverything()->howMany())->toBe(1);
});

it('leaves what was kept where the key cannot be had, since it may be had next time', function (): void {
    foreach ([ASealInMemory::thatWillNotOpen(), ASealInMemory::withNoSecureStorage()] as $seal) {
        $store = aStoreHoldingAReading();

        expect(new ClearingWhatCannotBeRead($seal, $store)->onOpening())->toBe(WhatWasKeptAtOpening::AsItWas)
            ->and($store->forgetEverything()->howMany())->toBe(1);
    }
});
