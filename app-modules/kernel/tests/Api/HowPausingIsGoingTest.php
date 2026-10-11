<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowPausingIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm following a pause takes, and what it carried there. */
function howPausingIsGoingReads(HowPausingIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('running'),
        answered: static fn(WhatPausingComesTo $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf('answered:%s', $report->agreement())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for each state, and carries the report and the obstacle', function (): void {
    $report = WhatPausingComesTo::said(WhatToDoWithTheDownloads::Pause, WhetherItWasRehearsed::Rehearsed, 'an-offer', '');

    expect(howPausingIsGoingReads(HowPausingIsGoing::stillRunning()))->toBe('running')
        ->and(howPausingIsGoingReads(HowPausingIsGoing::answered($report)))->toBe('answered:an-offer')
        ->and(howPausingIsGoingReads(HowPausingIsGoing::ended()))->toBe('ended')
        ->and(howPausingIsGoingReads(HowPausingIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe(sprintf('met:%s', KindOfObstacle::CredentialWasRefused->value));
});
