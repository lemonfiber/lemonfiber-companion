<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhetherItIsHeard;

it('reads the wire\'s boolean as the case it means', function (): void {
    expect(WhetherItIsHeard::said(wanted: true))->toBe(WhetherItIsHeard::Heard)
        ->and(WhetherItIsHeard::said(wanted: false))->toBe(WhetherItIsHeard::Silenced);
});

it('names a catalogue key for each case', function (): void {
    expect(WhetherItIsHeard::Silenced->saidOnTheScreen())->toBe('stacks.alerts.heard.silenced');
});
