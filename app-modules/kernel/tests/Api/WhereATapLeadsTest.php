<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowItIsWorded;
use Modules\Kernel\Api\RouteIsNotTheApps;
use Modules\Kernel\Api\WhereATapLeads;
use Modules\Kernel\Api\WhichWayItWent;

it('leads to a screen of the app, as a link under the scheme the app is opened at', function (): void {
    expect(WhereATapLeads::to('/stacks/the-loft')->asALink())->toBe('lemonfiber://stacks/the-loft');
});

it('refuses anywhere that is not a route of the app\'s', function (string $route): void {
    expect(static fn(): WhereATapLeads => WhereATapLeads::to($route))->toThrow(RouteIsNotTheApps::class);
})->with(['https://example.org/stacks', '//example.org/stacks', 'stacks/the-loft', '']);

it('words an alert by which way it went', function (): void {
    expect(HowItIsWorded::forAnAlert(WhichWayItWent::Onset))->toBe(HowItIsWorded::Onset)
        ->and(HowItIsWorded::forAnAlert(WhichWayItWent::Resolved))->toBe(HowItIsWorded::Resolved);
});
