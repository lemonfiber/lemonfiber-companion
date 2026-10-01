<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeOwed;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheVersionsSpoken;

it('fills its obstacle sentences with the versions that disagreed', function (): void {
    $met = Obstacle::versionsDisagree(TheVersionsSpoken::between(answered: 2, spoken: 1));

    expect(WhatAMemberTurnedOutToBeOwed::somethingStopped($met)->filling())->toBe(['answered' => 2, 'spoken' => 1]);
});

it('fills in nothing where nothing stood in the way', function (): void {
    expect(WhatAMemberTurnedOutToBeOwed::these([])->filling())->toBe([]);
});

it('is put right in the app\'s settings only where the local network was refused', function (): void {
    expect([
        WhatAMemberTurnedOutToBeOwed::somethingStopped(Obstacle::of(KindOfObstacle::LocalNetworkIsNotPermitted))->isPutRightInTheAppsSettings(),
        WhatAMemberTurnedOutToBeOwed::somethingStopped(Obstacle::of(KindOfObstacle::StackDidNotAnswer))->isPutRightInTheAppsSettings(),
        WhatAMemberTurnedOutToBeOwed::these([])->isPutRightInTheAppsSettings(),
    ])->toBe([true, false, false]);
});
