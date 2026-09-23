<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Unfilled;
use Modules\Kernel\Api\WhatNothingFills;

/** @return list<string> */
function theCapabilitiesUnfilled(WhatNothingFills $unfilled): array
{
    $named = [];

    foreach ($unfilled as $one) {
        $named[] = $one->capability()->named();
    }

    return $named;
}

it('keeps them in the order the core listed them', function (): void {
    // Unsorted on purpose: an order imposed here would be an opinion about
    // which unfilled capability matters most, and the core did not send one.
    $unfilled = WhatNothingFills::these(
        Unfilled::of(ServiceId::called('bazarr'), Capability::called('subtitle-provider')),
        Unfilled::of(ServiceId::called('sonarr'), Capability::called('indexer')),
    );

    expect(theCapabilitiesUnfilled($unfilled))->toBe(['subtitle-provider', 'indexer'])
        ->and($unfilled->count())->toBe(2);
});

it('has an empty form, which is everything being answered rather than a gap', function (): void {
    // A stack where nothing is unfilled says so. This has to be a value rather
    // than an absence, because an empty list and a list that could not be read
    // are the two answers a screen must never confuse — and a type that could
    // only be missing would make them identical.
    expect(theCapabilitiesUnfilled(WhatNothingFills::none()))->toBe([])
        ->and(WhatNothingFills::none()->count())->toBe(0);
});

it('is a list rather than whatever keys a variadic brought', function (): void {
    // The keys are the whole of what this asks. Named arguments give a variadic
    // string keys and this collection hands its items back out through its
    // iterator, so a listing that kept `first` and `then` holds the same two
    // items in the same order as one that reindexed — counting them, or reading
    // them, cannot tell the two apart. Only `array_keys` can.
    $unfilled = WhatNothingFills::these(
        first: Unfilled::of(ServiceId::called('bazarr'), Capability::called('subtitle-provider')),
        then: Unfilled::of(ServiceId::called('sonarr'), Capability::called('indexer')),
    );

    expect(array_keys(iterator_to_array($unfilled, preserve_keys: true)))->toBe([0, 1])
        ->and(count(theCapabilitiesUnfilled($unfilled)))->toBe(2);
});
