<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatTheWalkSaid;

use function sprintf;

/** One line carried out of an arm of what a walk said. */
final readonly class WhichArmTheWalkTook
{
    public function __construct(public string $said) {}
}

/** Which arm an answer about a walk takes, and what it carried there. */
function whichArmTheWalkTook(WhatTheWalkSaid $said): string
{
    return $said->either(
        nothing: static fn(): WhichArmTheWalkTook => new WhichArmTheWalkTook('nothing'),
        alive: static fn(): WhichArmTheWalkTook => new WhichArmTheWalkTook('alive'),
        said: static fn(ALineItSaid $line): WhichArmTheWalkTook => new WhichArmTheWalkTook(sprintf('said %s: %s', $line->step()->value, $line->said())),
        closed: static fn(): WhichArmTheWalkTook => new WhichArmTheWalkTook('closed'),
        met: static fn(Obstacle $why): WhichArmTheWalkTook => new WhichArmTheWalkTook(sprintf('met %s', $why->value)),
    )->said;
}

it('keeps the five things a subscription can answer about a walk apart', function (): void {
    $line = ALineItSaid::withoutDetail(WalkthroughStep::Grabbing, 'Sending the release to the download client');

    expect(whichArmTheWalkTook(WhatTheWalkSaid::nothing()))->toBe('nothing')
        ->and(whichArmTheWalkTook(WhatTheWalkSaid::aSignOfLife()))->toBe('alive')
        ->and(whichArmTheWalkTook(WhatTheWalkSaid::said($line)))->toBe('said grabbing: Sending the release to the download client')
        ->and(whichArmTheWalkTook(WhatTheWalkSaid::closed()))->toBe('closed')
        ->and(whichArmTheWalkTook(WhatTheWalkSaid::met(Obstacle::StackDidNotAnswer)))->toBe('met no_answer');
});
