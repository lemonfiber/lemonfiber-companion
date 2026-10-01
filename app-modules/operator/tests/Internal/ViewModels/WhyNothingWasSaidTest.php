<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\WhyNothingWasSaid;

it('is put right in the app\'s settings only where the local network was refused', function (): void {
    expect([
        WhyNothingWasSaid::because(Obstacle::of(KindOfObstacle::LocalNetworkIsNotPermitted))->isPutRightInTheAppsSettings(),
        WhyNothingWasSaid::because(Obstacle::of(KindOfObstacle::StackDidNotAnswer))->isPutRightInTheAppsSettings(),
        WhyNothingWasSaid::nothingStoppedIt()->isPutRightInTheAppsSettings(),
    ])->toBe([true, false, false]);
});
