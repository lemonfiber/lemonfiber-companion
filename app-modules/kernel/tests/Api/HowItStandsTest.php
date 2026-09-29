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

it('has a single word for each of them, for a line with no room for a sentence', function (): void {
    $said = [];

    foreach (HowItStands::cases() as $standing) {
        $said[$standing->value] = $standing->saidInAWord();
    }

    expect($said)->toBe([
        'healthy' => 'health.standing_short.healthy',
        'stopped' => 'health.standing_short.stopped',
        'unconfigured' => 'health.standing_short.unconfigured',
        'advisory' => 'health.standing_short.advisory',
        'degraded' => 'health.standing_short.degraded',
        'broken' => 'health.standing_short.broken',
        'critical' => 'health.standing_short.critical',
        'unknown' => 'health.standing_short.unknown',
    ]);
});
