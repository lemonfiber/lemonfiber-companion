<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowPuttingItBackIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhereTheDataGoes;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm following a restore takes, and what it carried there. */
function howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('running'),
        done: static fn(ACopyPutBack $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf('done:%s', $report->takenBy())),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s', $why->summary())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for each state, and carries the report, the refusal and the obstacle', function (): void {
    $report = ACopyPutBack::reported(ScopeOfACopy::theWholeStack(), '0.9.0', WhereTheDataGoes::whereItWas());
    $refused = ARefusalInItsWords::said('The backup could not be unpacked', '', WhatTheRefusalNamed::nothing());

    expect(howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing::stillRunning()))->toBe('running')
        ->and(howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing::done($report)))->toBe('done:0.9.0')
        ->and(howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing::refused($refused)))->toBe('refused:The backup could not be unpacked')
        ->and(howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing::ended()))->toBe('ended')
        ->and(howPuttingItBackIsGoingReads(HowPuttingItBackIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe(sprintf('met:%s', KindOfObstacle::CredentialWasRefused->value));
});
