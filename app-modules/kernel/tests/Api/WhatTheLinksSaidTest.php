<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhatTheLinksSaid;
use Modules\Kernel\Api\WhatTheRefusalNamed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm answered, as a word. */
function whichArmTheLinksTook(WhatTheLinksSaid $said): string
{
    return $said->either(
        links: static fn(TheLinks $links): TheWordCarriedOut => new TheWordCarriedOut(sprintf('links: %d unfilled', $links->unfilled()->count())),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met: %s', $why->kind()->name)),
    )->said;
}

it('takes a reader to the arm it was built on, with what that arm carries', function (): void {
    expect(whichArmTheLinksTook(WhatTheLinksSaid::links(TheLinks::of(WhatNothingFills::none()))))->toBe('links: 0 unfilled')
        ->and(whichArmTheLinksTook(WhatTheLinksSaid::refused(ARefusalInItsWords::said('It would not read', '', WhatTheRefusalNamed::nothing()))))->toBe('refused: It would not read')
        ->and(whichArmTheLinksTook(WhatTheLinksSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('met: StackDidNotAnswer');
});
