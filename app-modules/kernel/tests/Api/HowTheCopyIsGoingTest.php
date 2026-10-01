<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\HowACopyPaced;
use Modules\Kernel\Api\HowTheCopyIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm following a copy takes, and what it carried there. */
function howTheCopyIsGoingReads(HowTheCopyIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('running'),
        done: static fn(ACopyTaken $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf('done:%d', $report->pace()->moved())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for each state, and carries the report and the obstacle', function (): void {
    $report = ACopyTaken::reported(
        ScopeOfACopy::theWholeStack(),
        TheCopies::named(),
        HowACopyPaced::measured(moved: 42, budget: 100, brisk: true),
        WhetherItHoldsASecret::Secret,
        WhetherItWasRehearsed::CarriedOut,
    );

    expect(howTheCopyIsGoingReads(HowTheCopyIsGoing::stillRunning()))->toBe('running')
        ->and(howTheCopyIsGoingReads(HowTheCopyIsGoing::done($report)))->toBe('done:42')
        ->and(howTheCopyIsGoingReads(HowTheCopyIsGoing::ended()))->toBe('ended')
        ->and(howTheCopyIsGoingReads(HowTheCopyIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});
