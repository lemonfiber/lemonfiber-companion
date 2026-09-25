<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\WhatWroteACopy;

it('carries the version and the time as the stack stamped them', function (): void {
    $written = WhatWroteACopy::of('0.9.0', '2026-09-24T03:00:00Z');

    expect($written->version())->toBe('0.9.0')
        ->and($written->at())->toBe('2026-09-24T03:00:00Z');
});

it('refuses a half left blank, naming which', function (string $version, string $at, string $named): void {
    expect(fn(): WhatWroteACopy => WhatWroteACopy::of($version, $at))
        ->toThrow(KeepingSaysNothing::class, $named);
})->with([
    'the version' => [' ', '2026-09-24T03:00:00Z', '`product_version`'],
    'when' => ['0.9.0', "\n", '`created_at`'],
]);
