<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowTheVerbIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm following a verb takes, and what it carried there. */
function howTheVerbIsGoingReads(HowTheVerbIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('running'),
        done: static fn(WhatTheVerbCameTo $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf('done:%s', $report->was()->value)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
        moved: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('moved:%s', $why->summary())),
    )->said;
}

it('takes the arm for each state, and carries the report, the obstacle and the stack\'s words', function (): void {
    $moved = ARefusalInItsWords::said('What you agreed to is not what is offered now', '', WhatTheRefusalNamed::nothing());
    $report = WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
        TheStackEdits::none(),
        TheCommandLine::of('docker', 'compose', 'up', '-d'),
    );

    expect(howTheVerbIsGoingReads(HowTheVerbIsGoing::stillRunning()))->toBe('running')
        ->and(howTheVerbIsGoingReads(HowTheVerbIsGoing::done($report)))->toBe('done:carried_out')
        ->and(howTheVerbIsGoingReads(HowTheVerbIsGoing::ended()))->toBe('ended')
        ->and(howTheVerbIsGoingReads(HowTheVerbIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value))
        ->and(howTheVerbIsGoingReads(HowTheVerbIsGoing::moved($moved)))->toBe('moved:What you agreed to is not what is offered now')
        ->and(count($report->whatDidNotComeBack()))->toBe(0);
});
