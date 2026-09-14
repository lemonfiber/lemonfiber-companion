<?php

declare(strict_types=1);

use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatLeansOnIt;

/** Every id in a listing, in the order it holds them. */
function everyLeaner(WhatLeansOnIt $leaning): string
{
    $rows = [];

    foreach ($leaning as $one) {
        $rows[] = $one->named();
    }

    return implode(' | ', $rows);
}

it('N2-R8 — holds what stops with a service, in the stack\'s order', function (): void {
    $leaning = WhatLeansOnIt::these(ServiceId::called('qbittorrent'), ServiceId::called('prowlarr'));

    expect(everyLeaner($leaning))->toBe('qbittorrent | prowlarr')
        ->and($leaning->count())->toBe(2);
});

it('nothing leaning on it is the ordinary case, and a real answer', function (): void {
    // *Stopping this disturbs nothing else* is a sentence worth being able to
    // say, which an absent list could not.
    expect(WhatLeansOnIt::nothing()->count())->toBe(0)
        ->and(everyLeaner(WhatLeansOnIt::nothing()))->toBe('');
});

it('reads by position, whatever keys the variadic arrived with', function (): void {
    // Named arguments give a variadic string keys, and this collection
    // hands its items out again through its iterator — so the keys escape,
    // and everything downstream reads by position. The keys are what has to
    // be read back: `foreach` yields insertion order whatever they are, so
    // a collection that had kept `first` and `second` iterates identically
    // to one that reindexed, and an assertion over what `everyLeaner()`
    // collects is an assertion about its own accumulator. `Scrollback` and
    // `Stalled` read the keys for the same reason.
    $leaning = WhatLeansOnIt::these(
        first: ServiceId::called('qbittorrent'),
        second: ServiceId::called('prowlarr'),
    );

    expect(array_keys(iterator_to_array($leaning, preserve_keys: true)))->toBe([0, 1]);
});
