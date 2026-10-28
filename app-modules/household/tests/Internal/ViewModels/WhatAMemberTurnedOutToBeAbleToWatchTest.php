<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeAbleToWatch;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheVersionsSpoken;

it('fills its obstacle sentences with the versions that disagreed', function (): void {
    $met = Obstacle::versionsDisagree(TheVersionsSpoken::between(answered: 2, spoken: 1));

    expect(WhatAMemberTurnedOutToBeAbleToWatch::somethingStopped($met)->stopped?->filling())->toBe(['answered' => 2, 'spoken' => 1]);
});

it('fills in nothing where nothing stood in the way', function (): void {
    expect(WhatAMemberTurnedOutToBeAbleToWatch::these(null, [])->stopped)->toBeNull();
});

it('is put right in the app\'s settings only where the local network was refused', function (): void {
    expect([
        WhatAMemberTurnedOutToBeAbleToWatch::somethingStopped(Obstacle::of(KindOfObstacle::LocalNetworkIsNotPermitted))->stopped?->isPutRightInTheAppsSettings(),
        WhatAMemberTurnedOutToBeAbleToWatch::somethingStopped(Obstacle::of(KindOfObstacle::StackDidNotAnswer))->stopped?->isPutRightInTheAppsSettings(),
        WhatAMemberTurnedOutToBeAbleToWatch::these(null, [])->stopped?->isPutRightInTheAppsSettings(),
    ])->toBe([true, false, null]);
});
