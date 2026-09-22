<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\Wiring;
use Modules\Kernel\Api\Wirings;

function aWiringBy(string $service, string $capability): Wiring
{
    return Wiring::of(
        ServiceId::called($service),
        HowItReaches::asked(
            Capability::called($capability),
            Services::none(),
            WhatSettledIt::unfilled(),
        ),
    );
}

/** @return list<string> */
function theServicesWiring(Wirings $wirings): array
{
    $named = [];

    foreach ($wirings as $wiring) {
        $named[] = $wiring->by()->named();
    }

    return $named;
}

it('keeps them in the order the core listed them', function (): void {
    // Unsorted on purpose: an order imposed here would be an opinion about
    // which connection matters most, and the core sent none.
    $wirings = Wirings::these(aWiringBy('sonarr', 'download-client'), aWiringBy('bazarr', 'subtitle-provider'));

    expect(theServicesWiring($wirings))->toBe(['sonarr', 'bazarr'])
        ->and($wirings->count())->toBe(2);
});

it('has an empty form, which a stack with one service can honestly answer', function (): void {
    expect(theServicesWiring(Wirings::none()))->toBe([])
        ->and(Wirings::none()->count())->toBe(0);
});

it('is a list rather than whatever keys a variadic brought', function (): void {
    $wirings = Wirings::these(
        first: aWiringBy('sonarr', 'download-client'),
        then: aWiringBy('bazarr', 'subtitle-provider'),
    );

    expect(count(theServicesWiring($wirings)))->toBe(2);
});
