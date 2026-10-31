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
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheLineRunDid;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhereALiftStands;
use Modules\Kernel\Api\WhereTheLineStands;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/**
 * Which arm the work took, and what it carried there.
 *
 * @param WhatBecameOfTheWork<TheLineAsChanged> $became
 */
function whichArmTheWorkTook(WhatBecameOfTheWork $became): string
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

    expect(whichArmTheWorkTook(WhatBecameOfTheWork::underway(Job::named('a-job'))))->toBe('underway:a-job')
        ->and(whichArmTheWorkTook(WhatBecameOfTheWork::answered($line)))->toBe('answered:rehearsed')
        ->and(whichArmTheWorkTook(WhatBecameOfTheWork::ended()))->toBe('ended')
        ->and(whichArmTheWorkTook(WhatBecameOfTheWork::refused(ARefusalInItsWords::said('No download client to limit', '', WhatTheRefusalNamed::nothing()))))->toBe('refused:No download client to limit')
        ->and(whichArmTheWorkTook(WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe(sprintf('met:%s', KindOfObstacle::CredentialWasRefused->value));
});

it('takes a refusal because the offer moved to its own arm where one is given, and to the refused arm where none is', function (): void {
    $moved = WhatBecameOfTheWork::moved(ARefusalInItsWords::said('It moved', '', WhatTheRefusalNamed::nothing()));

    expect(whichArmTheWorkTook($moved))->toBe('refused:It moved')
        ->and($moved->eitherOrMoved(
            underway: static fn(): TheWordCarriedOut => new TheWordCarriedOut('underway'),
            answered: static fn(): TheWordCarriedOut => new TheWordCarriedOut('answered'),
            ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
            refused: static fn(): TheWordCarriedOut => new TheWordCarriedOut('refused'),
            met: static fn(): TheWordCarriedOut => new TheWordCarriedOut('met'),
            moved: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('moved:%s', $why->summary())),
        )->said)->toBe('moved:It moved');
});
