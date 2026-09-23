<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AlertSaysNothing;
use Modules\Kernel\Api\AnEventSetApart;
use Modules\Kernel\Api\SetApart;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Kernel\Api\WhetherItIsHeard;

use function sprintf;

it('N10-R8 — carries the preset, what it means and the exceptions made to it', function (): void {
    $told = WhatTheOperatorIsTold::byPreset('quiet', 'Only what needs you today', SetApart::of(AnEventSetApart::of('disk-low', WhetherItIsHeard::Heard)));

    expect($told->preset())->toBe('quiet')
        ->and($told->means())->toBe('Only what needs you today')
        ->and($told->exceptions())->toHaveCount(1);
});

it('refuses a preset without its name or without what it means', function (string $blank, string $field): void {
    $preset = $field === 'preset' ? $blank : 'quiet';
    $means = $field === 'means' ? $blank : 'Only what needs you today';

    expect(fn(): WhatTheOperatorIsTold => WhatTheOperatorIsTold::byPreset($preset, $means, SetApart::of()))
        ->toThrow(AlertSaysNothing::class, sprintf('`%s`', $field));
})->with([[' ', 'preset'], [' ', 'means']]);
