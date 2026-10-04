<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\WhatTheLineWasFound;
use Modules\Kernel\Api\WhereTheLineStands;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm an answer takes, and what it carried there. */
function whatTheLineWasFound(WhatTheLineWasFound $answer): string
{
    return $answer->either(
        shared: static fn(HowTheLineIsShared $line): TheWordCarriedOut => new TheWordCarriedOut(sprintf('shared:%s', $line->stands()->value)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('a line that could not be read is never an unlimited one', function (): void {
    expect(whatTheLineWasFound(WhatTheLineWasFound::shared(HowTheLineIsShared::standing(WhereTheLineStands::Unlimited, 'Nothing holds the stack back', 'Down: no limit', 'Up: no limit', Remarks::of(), Remarks::of()))))->toBe('shared:unlimited')
        ->and(whatTheLineWasFound(WhatTheLineWasFound::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});
