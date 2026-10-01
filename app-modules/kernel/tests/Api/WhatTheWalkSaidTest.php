<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatTheWalkSaid;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm an answer about a walk takes, and what it carried there. */
function theWordForWhichArmTheWalkTook(WhatTheWalkSaid $said): string
{
    return $said->either(
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
        alive: static fn(): TheWordCarriedOut => new TheWordCarriedOut('alive'),
        said: static fn(ALineItSaid $line): TheWordCarriedOut => new TheWordCarriedOut(sprintf('said %s: %s', $line->step()->value, $line->said())),
        closed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('closed'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met %s', $why->kind()->value)),
    )->said;
}

it('keeps the five things a subscription can answer about a walk apart', function (): void {
    $line = ALineItSaid::withoutDetail(WalkthroughStep::Grabbing, 'Sending the release to the download client');

    expect(theWordForWhichArmTheWalkTook(WhatTheWalkSaid::nothing()))->toBe('nothing')
        ->and(theWordForWhichArmTheWalkTook(WhatTheWalkSaid::aSignOfLife()))->toBe('alive')
        ->and(theWordForWhichArmTheWalkTook(WhatTheWalkSaid::said($line)))->toBe('said grabbing: Sending the release to the download client')
        ->and(theWordForWhichArmTheWalkTook(WhatTheWalkSaid::closed()))->toBe('closed')
        ->and(theWordForWhichArmTheWalkTook(WhatTheWalkSaid::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('met no_answer');
});
