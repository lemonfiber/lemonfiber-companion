<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhetherItGoesThroughTheTunnel;

it('N10-R5 — reads the wire\'s boolean as the case it means, and names a key for each', function (): void {
    expect(WhetherItGoesThroughTheTunnel::said(throughTunnel: true))->toBe(WhetherItGoesThroughTheTunnel::Through)
        ->and(WhetherItGoesThroughTheTunnel::said(throughTunnel: false))->toBe(WhetherItGoesThroughTheTunnel::Beside)
        ->and(WhetherItGoesThroughTheTunnel::Beside->saidOnTheScreen())->toBe('stacks.line.tunnel.beside');
});
