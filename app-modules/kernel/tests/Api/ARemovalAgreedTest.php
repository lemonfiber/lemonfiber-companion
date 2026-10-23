<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\ARemovalAgreed;
use Modules\Kernel\Api\HowFarTheRemovalReached;
use Modules\Kernel\Api\RemovalWasNotDescribed;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\WhatTheRemovalFound;

it('names the person and the reading it was answered under', function (): void {
    $who = SomebodyInTheHousehold::called('Anna');
    $described = ARemoval::described($who, 1, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Nothing, findings: WhatTheRemovalFound::of(), watchHistory: 'Their watch history goes with their account.', offer: 'removal-anna-7');
    $agreed = ARemovalAgreed::after($described);

    expect($agreed->who())->toBe($who)
        ->and($agreed->offer())->toBe('removal-anna-7');
});

it('refuses an answer that was already carried out', function (): void {
    $done = ARemoval::carriedOut(SomebodyInTheHousehold::called('Anna'), 1, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Everywhere, findings: WhatTheRemovalFound::of(), watchHistory: 'Their watch history goes with their account.');

    expect(fn(): ARemovalAgreed => ARemovalAgreed::after($done))->toThrow(RemovalWasNotDescribed::class, 'already been carried out');
});
