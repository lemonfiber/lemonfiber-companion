<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\HowTheVerbIsGoing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

/** One line carried out of an arm of a verb being followed. */
final readonly class WhichArmTheVerbTook
{
    public function __construct(public string $said) {}
}

/** Which arm following a verb takes, and what it carried there. */
function howTheVerbIsGoingReads(HowTheVerbIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): WhichArmTheVerbTook => new WhichArmTheVerbTook('running'),
        done: static fn(WhatTheVerbCameTo $report): WhichArmTheVerbTook => new WhichArmTheVerbTook(sprintf('done:%s', $report->was()->value)),
        ended: static fn(): WhichArmTheVerbTook => new WhichArmTheVerbTook('ended'),
        met: static fn(Obstacle $why): WhichArmTheVerbTook => new WhichArmTheVerbTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('takes the arm for each state, and carries the report and the obstacle', function (): void {
    $report = WhatTheVerbCameTo::reported(
        WhetherItWasRehearsed::CarriedOut,
        WhereTheServicesEndedUp::of(),
        TheServicesLeftOut::of(),
        ThePortsHeld::of(),
    );

    expect(howTheVerbIsGoingReads(HowTheVerbIsGoing::stillRunning()))->toBe('running')
        ->and(howTheVerbIsGoingReads(HowTheVerbIsGoing::done($report)))->toBe('done:carried_out')
        ->and(howTheVerbIsGoingReads(HowTheVerbIsGoing::ended()))->toBe('ended')
        ->and(howTheVerbIsGoingReads(HowTheVerbIsGoing::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value))
        ->and(count($report->whatDidNotComeBack()))->toBe(0);
});
