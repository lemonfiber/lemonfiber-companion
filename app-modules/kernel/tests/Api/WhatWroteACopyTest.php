<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\WhatWroteACopy;

it('carries the version and the time as the stack stamped them', function (): void {
    $written = WhatWroteACopy::of('0.9.0', AMomentAsWritten::of('2026-09-24T03:00:00Z'));

    expect($written->version())->toBe('0.9.0')
        ->and($written->at())->toEqual(AMomentAsWritten::of('2026-09-24T03:00:00Z'));
});

it('refuses a version left blank, naming it', function (): void {
    expect(fn(): WhatWroteACopy => WhatWroteACopy::of(' ', AMomentAsWritten::of('2026-09-24T03:00:00Z')))
        ->toThrow(KeepingSaysNothing::class, '`product_version`');
});
