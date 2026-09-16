<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\SomethingElseRunning;
use Modules\Kernel\Api\WhatElseIsRunning;
use Modules\Kernel\Api\WhatTheEngineCallsIt;

function somethingElse(string $name): SomethingElseRunning
{
    return SomethingElseRunning::called(
        WhatTheEngineCallsIt::called($name),
        'An image this stack never declared',
        HowAServiceRuns::Running,
    );
}

it('builds a list even from named arguments', function (): void {
    // A variadic collected from named arguments has string keys, and the type
    // this promises its readers is keyed by position.
    $running = WhatElseIsRunning::these(
        first: somethingElse('pihole'),
        second: somethingElse('homebridge'),
    );

    expect(array_keys(iterator_to_array($running, preserve_keys: true)))->toBe([0, 1]);
});

it('is empty only when the machine reported nothing else', function (): void {
    expect(WhatElseIsRunning::nothing()->isEmpty())->toBeTrue()
        ->and(WhatElseIsRunning::nothing()->count())->toBe(0)
        ->and(WhatElseIsRunning::these(somethingElse('pihole'))->isEmpty())->toBeFalse()
        ->and(WhatElseIsRunning::these(somethingElse('pihole'))->count())->toBe(1);
});
