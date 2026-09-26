<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\HowACopyPaced;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Tests\Support\WhatAScopeSays;

it('carries every part of the stack\'s report as it was given', function (): void {
    $pace = HowACopyPaced::measured(moved: 10, budget: 20, brisk: true);
    $taken = ACopyTaken::reported(
        ScopeOfACopy::oneService(ServiceId::called('sonarr')),
        TheCopies::named('lemonfiber-20260901-0300-sonarr'),
        $pace,
        WhetherItHoldsASecret::Secret,
        WhetherItWasRehearsed::Rehearsed,
    );

    expect(WhatAScopeSays::of($taken->scope()))->toBe('service:sonarr')
        ->and(iterator_to_array($taken->pruned(), preserve_keys: false))->toBe(['lemonfiber-20260901-0300-sonarr'])
        ->and($taken->pace())->toBe($pace)
        ->and($taken->holds())->toBe(WhetherItHoldsASecret::Secret)
        ->and($taken->was())->toBe(WhetherItWasRehearsed::Rehearsed);
});
