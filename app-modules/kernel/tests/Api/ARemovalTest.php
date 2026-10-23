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
    $described = ARemoval::described($who, 2, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Nothing, findings: $found, watchHistory: 'Their watch history stays with the media server.', offer: ' removal-anna-2 ');
    $done = ARemoval::carriedOut($who, 0, asksThroughTheRequestService: false, revoked: HowFarTheRemovalReached::Everywhere, findings: $found, watchHistory: 'Their watch history goes with their account.');

    expect($described->who())->toBe($who)
        ->and($described->wasCarriedOut())->toBeFalse()
        ->and($described->requests())->toBe(2)
        ->and($described->asksThroughTheRequestService())->toBeTrue()
        ->and($described->revoked())->toBe(HowFarTheRemovalReached::Nothing)
        ->and($described->findings())->toBe($found)
        ->and($described->watchHistory())->toBe('Their watch history stays with the media server.')
        ->and($described->offer())->toBe('removal-anna-2')
        ->and($done->wasCarriedOut())->toBeTrue()
        ->and($done->requests())->toBe(0)
        ->and($done->asksThroughTheRequestService())->toBeFalse()
        ->and($done->revoked())->toBe(HowFarTheRemovalReached::Everywhere)
        ->and($done->watchHistory())->toBe('Their watch history goes with their account.')
        ->and($done->offer())->toBe('');
});

it('refuses a count of requests below none, however it was answered', function (): void {
    $who = SomebodyInTheHousehold::called('Anna');

    expect(fn(): ARemoval => ARemoval::described($who, -1, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Nothing, findings: WhatTheRemovalFound::of(), watchHistory: 'Their watch history goes with their account.', offer: 'removal-anna-1'))
        ->toThrow(RemovalSaysNothing::class, 'its `requests` at -1')
        ->and(fn(): ARemoval => ARemoval::carriedOut($who, -2, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Everywhere, findings: WhatTheRemovalFound::of(), watchHistory: 'Their watch history goes with their account.'))
        ->toThrow(RemovalSaysNothing::class, 'its `requests` at -2');
});

it('refuses a reading that names no offer, or a removal silent on the watch history', function (): void {
    $who = SomebodyInTheHousehold::called('Anna');

    expect(fn(): ARemoval => ARemoval::described($who, 1, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Nothing, findings: WhatTheRemovalFound::of(), watchHistory: 'Their watch history goes with their account.', offer: ' '))
        ->toThrow(RemovalSaysNothing::class, 'its `offer` blank')
        ->and(fn(): ARemoval => ARemoval::described($who, 1, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Nothing, findings: WhatTheRemovalFound::of(), watchHistory: ' ', offer: 'removal-anna-1'))
        ->toThrow(RemovalSaysNothing::class, 'its `watch-history` blank')
        ->and(fn(): ARemoval => ARemoval::carriedOut($who, 1, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Everywhere, findings: WhatTheRemovalFound::of(), watchHistory: ''))
        ->toThrow(RemovalSaysNothing::class, 'its `watch-history` blank');
});
