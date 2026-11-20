<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowTheCodeStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Modules\Kernel\Api\WhenToTryAgain;
use Modules\Kernel\Api\Whose;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

function whichArmTheCodeTook(HowTheCodeStands $stands): string
{
    return $stands->either(
        opened: static fn(Session $session, Instant $until): TheWordCarriedOut => new TheWordCarriedOut(sprintf('opened %s until %d', $session->forTheHeader(), $until->epochSeconds())),
        waiting: static fn(Instant $lapses): TheWordCarriedOut => new TheWordCarriedOut(sprintf('waiting until %d', $lapses->epochSeconds())),
        lapsed: static fn(WhatTheHouseholdWasTold $told): TheWordCarriedOut => new TheWordCarriedOut(sprintf('lapsed: %s', $told->sentence())),
        refused: static fn(Obstacle $why, WhenToTryAgain $tryAgain): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s %s', $why->kind()->value, $tryAgain->isSaid() ? $tryAgain->wait()->inSeconds() : 'unsaid')),
    )->said;
}

it('takes the arm each answer was built for', function (): void {
    expect([
        whichArmTheCodeTook(HowTheCodeStands::opened(Session::of('a-session'), Instant::atEpochSeconds(90), Whose::member('robin'))),
        whichArmTheCodeTook(HowTheCodeStands::waiting(Instant::atEpochSeconds(60))),
        whichArmTheCodeTook(HowTheCodeStands::lapsed(WhatTheHouseholdWasTold::said('Gone.', ''))),
        whichArmTheCodeTook(HowTheCodeStands::refused(Obstacle::of(KindOfObstacle::TooManyAttempts), WhenToTryAgain::after(HowLong::ofSeconds(30)))),
    ])->toBe(['opened a-session until 90', 'waiting until 60', 'lapsed: Gone.', 'refused too_many_attempts 30']);
});
