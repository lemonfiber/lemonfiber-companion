<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\HowFarTheRemovalReached;
use Modules\Kernel\Api\RemovalSaysNothing;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\WhatTheRemovalFound;

it('carries what taking somebody out would cost, told apart from what it did', function (): void {
    $who = SomebodyInTheHousehold::called('Anna');
    $found = WhatTheRemovalFound::of('The request service answered slowly');
    $described = ARemoval::described($who, 2, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Nothing, findings: $found);
    $done = ARemoval::carriedOut($who, 0, asksThroughTheRequestService: false, revoked: HowFarTheRemovalReached::Everywhere, findings: $found);

    expect($described->who())->toBe($who)
        ->and($described->wasCarriedOut())->toBeFalse()
        ->and($described->requests())->toBe(2)
        ->and($described->asksThroughTheRequestService())->toBeTrue()
        ->and($described->revoked())->toBe(HowFarTheRemovalReached::Nothing)
        ->and($described->findings())->toBe($found)
        ->and($done->wasCarriedOut())->toBeTrue()
        ->and($done->requests())->toBe(0)
        ->and($done->asksThroughTheRequestService())->toBeFalse()
        ->and($done->revoked())->toBe(HowFarTheRemovalReached::Everywhere);
});

it('refuses a count of requests below none, however it was answered', function (): void {
    $who = SomebodyInTheHousehold::called('Anna');

    expect(fn(): ARemoval => ARemoval::described($who, -1, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Nothing, findings: WhatTheRemovalFound::of()))
        ->toThrow(RemovalSaysNothing::class, 'its `requests` at -1')
        ->and(fn(): ARemoval => ARemoval::carriedOut($who, -2, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Everywhere, findings: WhatTheRemovalFound::of()))
        ->toThrow(RemovalSaysNothing::class, 'its `requests` at -2');
});
