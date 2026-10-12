<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowTheLineIsShared;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\TheLineAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheLineChange;
use Modules\Kernel\Api\WhatTheLineRunDid;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhereALiftStands;
use Modules\Kernel\Api\WhereTheLineStands;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm changing the line took, and what it carried there. */
function whichArmTheLineChangeTook(WhatBecameOfTheLineChange $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway:%s', $job->shown())),
        answered: static fn(TheLineAsChanged $line): TheWordCarriedOut => new TheWordCarriedOut(sprintf('answered:%s', $line->did()->value)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for each state, and carries what each holds', function (): void {
    $line = TheLineAsChanged::said(
        HowTheLineIsShared::standing(WhereTheLineStands::Limited, 'The stack takes half', 'half of 100 Mbit/s', 'a quarter of 10 Mbit/s', Remarks::of(), Remarks::of()),
        WhatTheLineRunDid::Rehearsed,
        WhereALiftStands::none(),
    );

    expect(whichArmTheLineChangeTook(WhatBecameOfTheLineChange::underway(Job::named('a-job'))))->toBe('underway:a-job')
        ->and(whichArmTheLineChangeTook(WhatBecameOfTheLineChange::answered($line)))->toBe('answered:rehearsed')
        ->and(whichArmTheLineChangeTook(WhatBecameOfTheLineChange::ended()))->toBe('ended')
        ->and(whichArmTheLineChangeTook(WhatBecameOfTheLineChange::refused(ARefusalInItsWords::said('No download client to limit', '', WhatTheRefusalNamed::nothing()))))->toBe('refused:No download client to limit')
        ->and(whichArmTheLineChangeTook(WhatBecameOfTheLineChange::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe(sprintf('met:%s', KindOfObstacle::CredentialWasRefused->value));
});
