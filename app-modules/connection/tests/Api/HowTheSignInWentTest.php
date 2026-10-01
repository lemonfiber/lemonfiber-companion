<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function array_filter;
use function array_values;
use function expect;
use function it;

use Modules\Connection\Api\HowTheSignInWent;

it('is put right in the app\'s settings only where the local network was refused', function (): void {
    expect(array_values(array_filter(
        HowTheSignInWent::cases(),
        static fn(HowTheSignInWent $went): bool => $went->isPutRightInTheAppsSettings(),
    )))->toBe([HowTheSignInWent::TheNetworkIsNotPermitted]);
});
