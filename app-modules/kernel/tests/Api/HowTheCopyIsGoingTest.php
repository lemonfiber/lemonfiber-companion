<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\HowACopyPaced;
use Modules\Kernel\Api\HowTheCopyIsGoing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhetherItHoldsASecret;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

/** One line carried out of an arm of a copy being followed. */
final readonly class WhichArmTheCopyTook
{
    public function __construct(public string $said) {}
}

/** Which arm following a copy takes, and what it carried there. */
function howTheCopyIsGoingReads(HowTheCopyIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): WhichArmTheCopyTook => new WhichArmTheCopyTook('running'),
        done: static fn(ACopyTaken $report): WhichArmTheCopyTook => new WhichArmTheCopyTook(sprintf('done:%d', $report->pace()->moved())),
        ended: static fn(): WhichArmTheCopyTook => new WhichArmTheCopyTook('ended'),
        met: static fn(Obstacle $why): WhichArmTheCopyTook => new WhichArmTheCopyTook(sprintf('met:%s', $why->value)),
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
        ->and(howTheCopyIsGoingReads(HowTheCopyIsGoing::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
