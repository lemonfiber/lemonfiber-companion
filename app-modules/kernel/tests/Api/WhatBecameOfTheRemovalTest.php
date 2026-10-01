<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\HowFarTheRemovalReached;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RemovalSaysNothing;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\WhatBecameOfTheRemoval;
use Modules\Kernel\Api\WhatTheRemovalFound;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm an answer took, and what it carried. */
function theWordForWhichArmTheRemovalTook(WhatBecameOfTheRemoval $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway:%s', $job->shown())),
        answered: static fn(ARemoval $removal): TheWordCarriedOut => new TheWordCarriedOut(sprintf('answered:%s', $removal->who()->name())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(string $because): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s', $because)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm it was made on, and carries what it was made with', function (): void {
    $removal = ARemoval::carriedOut(SomebodyInTheHousehold::called('Anna'), 0, asksThroughTheRequestService: false, revoked: HowFarTheRemovalReached::Everywhere, findings: WhatTheRemovalFound::of());

    expect(theWordForWhichArmTheRemovalTook(WhatBecameOfTheRemoval::underway(Job::named('j-1'))))->toBe('underway:j-1')
        ->and(theWordForWhichArmTheRemovalTook(WhatBecameOfTheRemoval::answered($removal)))->toBe('answered:Anna')
        ->and(theWordForWhichArmTheRemovalTook(WhatBecameOfTheRemoval::ended()))->toBe('ended')
        ->and(theWordForWhichArmTheRemovalTook(WhatBecameOfTheRemoval::refused('Nobody is called Anna here')))->toBe('refused:Nobody is called Anna here')
        ->and(theWordForWhichArmTheRemovalTook(WhatBecameOfTheRemoval::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});

it('refuses a refusal with nothing said', function (): void {
    expect(fn(): WhatBecameOfTheRemoval => WhatBecameOfTheRemoval::refused(' '))->toThrow(RemovalSaysNothing::class, 'its `reason` blank');
});
