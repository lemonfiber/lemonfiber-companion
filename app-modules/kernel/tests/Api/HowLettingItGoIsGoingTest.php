<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\HowLettingItGoIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm following stopping seeding takes, and what it carried there. */
function howLettingItGoIsGoingReads(HowLettingItGoIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('running'),
        done: static fn(ADownloadLetGo $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf('done:%s', $report->name())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for each state, and carries the report and the obstacle', function (): void {
    $report = ADownloadLetGo::reported('Show.Season1', 1, WhetherItWasRehearsed::CarriedOut);

    expect(howLettingItGoIsGoingReads(HowLettingItGoIsGoing::stillRunning()))->toBe('running')
        ->and(howLettingItGoIsGoingReads(HowLettingItGoIsGoing::done($report)))->toBe('done:Show.Season1')
        ->and(howLettingItGoIsGoingReads(HowLettingItGoIsGoing::ended()))->toBe('ended')
        ->and(howLettingItGoIsGoingReads(HowLettingItGoIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe(sprintf('met:%s', KindOfObstacle::CredentialWasRefused->value));
});
