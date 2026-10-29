<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ADirectionHeld;
use Modules\Kernel\Api\ARateGiven;
use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\WhatALimitCameTo;
use Modules\Kernel\Api\WhichRate;

use function sprintf;

it('keeps the verdict and each rate the client gave, in its order', function (): void {
    $held = ADirectionHeld::said(WhatALimitCameTo::Overrunning, ARateGiven::of(WhichRate::Asked, 2_000_000), ARateGiven::of(WhichRate::Moving, 3_000_000));

    expect($held->verdict())->toBe(WhatALimitCameTo::Overrunning)
        ->and(array_map(static fn(ARateGiven $rate): string => sprintf('%s %d', $rate->which()->value, $rate->bytesASecond()), iterator_to_array($held, preserve_keys: false)))
        ->toBe(['asked 2000000', 'moving 3000000'])
        ->and(iterator_to_array(ADirectionHeld::said(WhatALimitCameTo::Unasked), preserve_keys: false))->toBe([]);
});

it('refuses a rate below nothing, naming which, and holds a rate of nothing', function (): void {
    expect(static fn(): ARateGiven => ARateGiven::of(WhichRate::Accepted, -1))
        ->toThrow(LineSaysNothing::class, '`accepted`')
        ->and(ARateGiven::of(WhichRate::Accepted, 0)->bytesASecond())->toBe(0);
});

it('names a catalogue line for each rate', function (): void {
    expect(WhichRate::Moving->saidOnTheScreen())->toBe('stacks.line_change.rate.moving');
});
