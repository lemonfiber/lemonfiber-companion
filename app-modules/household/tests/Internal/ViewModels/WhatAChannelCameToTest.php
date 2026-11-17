<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Household\Internal\ViewModels\WhatAChannelCameTo;
use Modules\Household\Internal\ViewModels\WhatStoppedAMember;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;

/**
 * Each of its fields, in order. Named for this file.
 *
 * @return list{string, string, string, bool}
 */
function whatItHolds(WhatAChannelCameTo $came): array
{
    return [$came->channel, $came->inTheHousesWords, $came->key, $came->stopped instanceof WhatStoppedAMember];
}

it('holds each way of saying it, and nothing else', function (): void {
    $stopped = WhatStoppedAMember::by(Obstacle::of(KindOfObstacle::StackDidNotAnswer));

    expect(whatItHolds(WhatAChannelCameTo::nothing()))->toBe(['', '', '', false])
        ->and(whatItHolds(WhatAChannelCameTo::said('NPO 1', 'Only at home.')))->toBe(['NPO 1', 'Only at home.', '', false])
        ->and(whatItHolds(WhatAChannelCameTo::stopped('NPO 1', $stopped)))->toBe(['NPO 1', '', '', true])
        ->and(whatItHolds(WhatAChannelCameTo::inOurWords('NPO 1', 'household.play.no_player')))->toBe(['NPO 1', '', 'household.play.no_player', false])
        ->and(whatItHolds(WhatAChannelCameTo::inOurWords('NPO 1', '')))->toBe(['', '', '', false]);
});

it('is nothing only where it says nothing', function (): void {
    expect(WhatAChannelCameTo::nothing()->isNothing())->toBeTrue()
        ->and(WhatAChannelCameTo::said('NPO 1', 'Only at home.')->isNothing())->toBeFalse()
        ->and(WhatAChannelCameTo::inOurWords('NPO 1', 'household.play.no_player')->isNothing())->toBeFalse()
        ->and(WhatAChannelCameTo::stopped('NPO 1', WhatStoppedAMember::by(Obstacle::of(KindOfObstacle::StackDidNotAnswer)))->isNothing())->toBeFalse();
});
