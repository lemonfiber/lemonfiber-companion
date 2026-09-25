<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\ServiceId;
use Tests\Support\WhatAScopeSays;

it('asks for the whole stack by narrowing to no service, and states it as the whole stack', function (): void {
    $asked = ACopyAsked::ofTheWholeStack();

    expect($asked->narrowedTo()->isEmpty())->toBeTrue()
        ->and(WhatAScopeSays::of($asked->scope()))->toBe('whole');
});

it('asks for one service by narrowing to it, and states it as that service', function (): void {
    $asked = ACopyAsked::ofOneService(ServiceId::called('sonarr'));
    $narrowed = [];

    foreach ($asked->narrowedTo() as $service) {
        $narrowed[] = $service->named();
    }

    expect($narrowed)->toBe(['sonarr'])
        ->and(WhatAScopeSays::of($asked->scope()))->toBe('service:sonarr');
});
