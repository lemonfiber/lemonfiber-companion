<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowItStands;

it('has the eight words the core has for how a stack stands, each its own line', function (): void {
    $said = [];

    foreach (HowItStands::cases() as $standing) {
        $said[$standing->value] = $standing->saidOnTheScreen();
    }

    expect($said)->toBe([
        'healthy' => 'health.standing.healthy',
        'stopped' => 'health.standing.stopped',
        'unconfigured' => 'health.standing.unconfigured',
        'advisory' => 'health.standing.advisory',
        'degraded' => 'health.standing.degraded',
        'broken' => 'health.standing.broken',
        'critical' => 'health.standing.critical',
        'unknown' => 'health.standing.unknown',
    ]);
});
