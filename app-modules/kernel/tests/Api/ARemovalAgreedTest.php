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

it('names the person the reading was answered under', function (): void {
    $who = SomebodyInTheHousehold::called('Anna');
    $described = ARemoval::described($who, 1, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Nothing, findings: WhatTheRemovalFound::of());

    expect(ARemovalAgreed::after($described)->who())->toBe($who);
});

it('refuses an answer that was already carried out', function (): void {
    $done = ARemoval::carriedOut(SomebodyInTheHousehold::called('Anna'), 1, asksThroughTheRequestService: true, revoked: HowFarTheRemovalReached::Everywhere, findings: WhatTheRemovalFound::of());

    expect(fn(): ARemovalAgreed => ARemovalAgreed::after($done))->toThrow(RemovalWasNotDescribed::class, 'already been carried out');
});
