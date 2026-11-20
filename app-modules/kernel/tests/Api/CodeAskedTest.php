<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACodeToApprove;
use Modules\Kernel\Api\AWaitingCode;
use Modules\Kernel\Api\CodeAsked;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Modules\Kernel\Api\WhenToTryAgain;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

function whichArmAskingTook(CodeAsked $asked): string
{
    return $asked->either(
        issued: static fn(ACodeToApprove $code): TheWordCarriedOut => new TheWordCarriedOut(sprintf('issued %s', $code->code)),
        byPassword: static fn(WhatTheHouseholdWasTold $told): TheWordCarriedOut => new TheWordCarriedOut(sprintf('by password: %s', $told->sentence())),
        refused: static fn(Obstacle $why, WhenToTryAgain $tryAgain): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s %s', $why->kind()->value, $tryAgain->isSaid() ? $tryAgain->wait()->inSeconds() : 'unsaid')),
    )->said;
}

it('takes the arm each answer was built for', function (): void {
    expect([
        whichArmAskingTook(CodeAsked::issued(ACodeToApprove::issued('ABCD', 'https://approve.example/link', Instant::atEpochSeconds(90), AWaitingCode::answered('a-handle')))),
        whichArmAskingTook(CodeAsked::byPassword(WhatTheHouseholdWasTold::said('By password here.', ''))),
        whichArmAskingTook(CodeAsked::refused(Obstacle::of(KindOfObstacle::TooManyAttempts), WhenToTryAgain::after(HowLong::ofSeconds(60)))),
    ])->toBe(['issued ABCD', 'by password: By password here.', 'refused too_many_attempts 60']);
});
