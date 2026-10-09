<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\HowTheUpdateIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatTheRefusalNamed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm following an update takes, and what it carried there. */
function howTheUpdateIsGoingReads(HowTheUpdateIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('running'),
        done: static fn(Upkeep $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf('done:%s', $report->againstThePins()->value)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
        moved: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('moved:%s', $why->summary())),
    )->said;
}

it('takes the arm for each state, and carries the report, the obstacle and the stack\'s words', function (): void {
    $report = Upkeep::reported(AgainstThePins::Current, Releases::none(), Services::none(), Services::none(), HowServicesTookIt::none(), HowTheNotesStand::Current, TheStackEdits::none());
    $moved = ARefusalInItsWords::said('What you agreed to is not what is offered now', '', WhatTheRefusalNamed::nothing());

    expect(howTheUpdateIsGoingReads(HowTheUpdateIsGoing::stillRunning()))->toBe('running')
        ->and(howTheUpdateIsGoingReads(HowTheUpdateIsGoing::done($report)))->toBe(sprintf('done:%s', AgainstThePins::Current->value))
        ->and(howTheUpdateIsGoingReads(HowTheUpdateIsGoing::ended()))->toBe('ended')
        ->and(howTheUpdateIsGoingReads(HowTheUpdateIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value))
        ->and(howTheUpdateIsGoingReads(HowTheUpdateIsGoing::moved($moved)))->toBe('moved:What you agreed to is not what is offered now');
});
