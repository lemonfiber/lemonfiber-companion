<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhatLemonfiberAsksFor;
use Modules\Kernel\Api\WhetherItIsAllowed;

it('reads the wire\'s boolean as the case it means', function (): void {
    expect(WhetherItIsAllowed::said(allowed: true))->toBe(WhetherItIsAllowed::Allowed)
        ->and(WhetherItIsAllowed::said(allowed: false))->toBe(WhetherItIsAllowed::SwitchedOff);
});

it('names a catalogue key for each case, and for each request lemonfiber makes', function (): void {
    expect(WhetherItIsAllowed::SwitchedOff->saidOnTheScreen())->toBe('stacks.outbound.allowed.switched_off')
        ->and(WhatLemonfiberAsksFor::Echo->saidOnTheScreen())->toBe('stacks.outbound.asks_for.echo');
});
