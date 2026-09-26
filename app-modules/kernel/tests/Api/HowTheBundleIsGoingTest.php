<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\HowTheBundleIsGoing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\ThePiecesOfABundle;
use Modules\Kernel\Api\TheTermsOfABundle;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Kernel\Api\WhenABundleWasTaken;
use Modules\Kernel\Api\WhereABundleIs;

use function sprintf;

/** One line carried out of an arm of a bundle being followed. */
final readonly class WhichArmTheBundleTook
{
    public function __construct(public string $said) {}
}

/** Which arm following a bundle takes, and what it carried there. */
function howTheBundleIsGoingReads(HowTheBundleIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): WhichArmTheBundleTook => new WhichArmTheBundleTook('running'),
        done: static fn(ABundle $bundle): WhichArmTheBundleTook => new WhichArmTheBundleTook(sprintf('done:%d', $bundle->bytes())),
        refused: static fn(string $said): WhichArmTheBundleTook => new WhichArmTheBundleTook(sprintf('refused:%s', $said)),
        ended: static fn(): WhichArmTheBundleTook => new WhichArmTheBundleTook('ended'),
        met: static fn(Obstacle $why): WhichArmTheBundleTook => new WhichArmTheBundleTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('takes the arm for each state, and carries the bundle, the refusal and the obstacle', function (): void {
    $bundle = ABundle::reported(
        4096,
        WhereABundleIs::unsaid(),
        TheTermsOfABundle::stated('the last 200 lines of each service', WhatFilenamesShow::Replaced, SettingsToReveal::none()),
        ThePiecesOfABundle::of(),
        Remarks::of(),
        WhenABundleWasTaken::at('2026-09-26T10:00:00Z', '1.4.0', '2026.09'),
    );

    expect(howTheBundleIsGoingReads(HowTheBundleIsGoing::stillRunning()))->toBe('running')
        ->and(howTheBundleIsGoingReads(HowTheBundleIsGoing::done($bundle)))->toBe('done:4096')
        ->and(howTheBundleIsGoingReads(HowTheBundleIsGoing::refused('The bundle still held something that reads as a credential')))
        ->toBe('refused:The bundle still held something that reads as a credential')
        ->and(howTheBundleIsGoingReads(HowTheBundleIsGoing::ended()))->toBe('ended')
        ->and(howTheBundleIsGoingReads(HowTheBundleIsGoing::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
