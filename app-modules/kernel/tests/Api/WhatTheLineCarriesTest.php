<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowTheLineWasMeasured;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\WhatTheLineCarries;
use Modules\Kernel\Api\WhetherItGoesThroughTheTunnel;

use function sprintf;

it('N10-R4, N10-R5 — carries down and up apart, how they were measured, when, and over which path', function (): void {
    $line = WhatTheLineCarries::measured(12_500_000, 2_500_000, HowTheLineWasMeasured::Observed, Instant::atEpochSeconds(1_790_100_000), WhetherItGoesThroughTheTunnel::Through);

    expect($line->down())->toBe(12_500_000)
        ->and($line->up())->toBe(2_500_000)
        ->and($line->measuredAs())->toBe(HowTheLineWasMeasured::Observed)
        ->and($line->taken()->epochSeconds())->toBe(1_790_100_000)
        ->and($line->tunnel())->toBe(WhetherItGoesThroughTheTunnel::Through);
});

it('refuses a figure below zero in either direction', function (int $down, int $up, string $field): void {
    expect(fn(): WhatTheLineCarries => WhatTheLineCarries::measured($down, $up, HowTheLineWasMeasured::Declared, Instant::atEpochSeconds(1), WhetherItGoesThroughTheTunnel::Beside))
        ->toThrow(LineSaysNothing::class, sprintf('`%s`', $field));
})->with([[-1, 5, 'down'], [5, -1, 'up']]);

it('takes nought in either direction as a figure, since a line can be measured carrying nothing', function (): void {
    $line = WhatTheLineCarries::measured(0, 0, HowTheLineWasMeasured::Observed, Instant::atEpochSeconds(1), WhetherItGoesThroughTheTunnel::Through);

    expect([$line->down(), $line->up()])->toBe([0, 0]);
});
