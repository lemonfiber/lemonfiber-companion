<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\HowPuttingItBackIsGoing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\WhereTheDataGoes;

use function sprintf;

/** One line carried out of an arm of a restore being followed. */
final readonly class WhichArmPuttingItBackTook
{
    public function __construct(public string $said) {}
}

/** Which arm following a restore takes, and what it carried there. */
function howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): WhichArmPuttingItBackTook => new WhichArmPuttingItBackTook('running'),
        done: static fn(ACopyPutBack $report): WhichArmPuttingItBackTook => new WhichArmPuttingItBackTook(sprintf('done:%s', $report->takenBy())),
        ended: static fn(): WhichArmPuttingItBackTook => new WhichArmPuttingItBackTook('ended'),
        met: static fn(Obstacle $why): WhichArmPuttingItBackTook => new WhichArmPuttingItBackTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('takes the arm for each state, and carries the report and the obstacle', function (): void {
    $report = ACopyPutBack::reported(ScopeOfACopy::theWholeStack(), '0.9.0', WhereTheDataGoes::whereItWas());

    expect(howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing::stillRunning()))->toBe('running')
        ->and(howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing::done($report)))->toBe('done:0.9.0')
        ->and(howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing::ended()))->toBe('ended')
        ->and(howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing::met(Obstacle::CredentialWasRefused)))->toBe(sprintf('met:%s', Obstacle::CredentialWasRefused->value));
});
