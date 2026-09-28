<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\HowLettingItGoIsGoing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

/** One line carried out of an arm of stopping seeding being followed. */
final readonly class WhichArmLettingItGoTook
{
    public function __construct(public string $said) {}
}

/** Which arm following stopping seeding takes, and what it carried there. */
function howLettingItGoIsGoingReads(HowLettingItGoIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): WhichArmLettingItGoTook => new WhichArmLettingItGoTook('running'),
        done: static fn(ADownloadLetGo $report): WhichArmLettingItGoTook => new WhichArmLettingItGoTook(sprintf('done:%s', $report->name())),
        ended: static fn(): WhichArmLettingItGoTook => new WhichArmLettingItGoTook('ended'),
        met: static fn(Obstacle $why): WhichArmLettingItGoTook => new WhichArmLettingItGoTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('takes the arm for each state, and carries the report and the obstacle', function (): void {
    $report = ADownloadLetGo::reported('Show.Season1', 1, WhetherItWasRehearsed::CarriedOut);

    expect(howLettingItGoIsGoingReads(HowLettingItGoIsGoing::stillRunning()))->toBe('running')
        ->and(howLettingItGoIsGoingReads(HowLettingItGoIsGoing::done($report)))->toBe('done:Show.Season1')
        ->and(howLettingItGoIsGoingReads(HowLettingItGoIsGoing::ended()))->toBe('ended')
        ->and(howLettingItGoIsGoingReads(HowLettingItGoIsGoing::met(Obstacle::CredentialWasRefused)))->toBe(sprintf('met:%s', Obstacle::CredentialWasRefused->value));
});
