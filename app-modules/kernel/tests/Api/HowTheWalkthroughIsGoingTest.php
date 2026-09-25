<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\HowTheWalkthroughIsGoing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheLinesItSaid;
use Modules\Kernel\Api\WhatComesNext;
use Modules\Kernel\Api\WhatCouldBeWalkedInstead;
use Modules\Kernel\Api\WhatWasWalked;
use Modules\Kernel\Api\WhereTheWalkthroughIs;
use Modules\Kernel\Api\WhichWalk;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichGoingArm
{
    public function __construct(public string $said) {}
}

/** Which arm a reading takes, as one line. */
function whichWayItIsGoing(HowTheWalkthroughIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): WhichGoingArm => new WhichGoingArm('running'),
        done: static fn(AWalkthrough $walk): WhichGoingArm => new WhichGoingArm(sprintf('done:%s', $walk->state()->value)),
        ended: static fn(): WhichGoingArm => new WhichGoingArm('ended'),
        met: static fn(Obstacle $why): WhichGoingArm => new WhichGoingArm(sprintf('met:%s', $why->name)),
    )->said;
}

it('says each of its four states through its own arm', function (): void {
    $walk = AWalkthrough::reported(
        WhichWalk::Pipeline,
        WhereTheWalkthroughIs::Complete,
        'That it works.',
        WhatWasWalked::nothingChosen(),
        TheLinesItSaid::of(),
        WhatCouldBeWalkedInstead::of(),
        WhatComesNext::of(),
        inBackground: false,
        alreadyHere: false,
    );

    expect(whichWayItIsGoing(HowTheWalkthroughIsGoing::stillRunning()))->toBe('running')
        ->and(whichWayItIsGoing(HowTheWalkthroughIsGoing::done($walk)))->toBe('done:complete')
        ->and(whichWayItIsGoing(HowTheWalkthroughIsGoing::ended()))->toBe('ended')
        ->and(whichWayItIsGoing(HowTheWalkthroughIsGoing::met(Obstacle::StackDidNotAnswer)))->toBe('met:StackDidNotAnswer');
});
