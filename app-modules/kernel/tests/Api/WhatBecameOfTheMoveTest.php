<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheMoveSaysNothing;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheStandingBeside;
use Modules\Kernel\Api\WhatBecameOfTheMove;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm an answer took, and what it carried, folded to one line. */
function theWordForWhereTheMoveGotTo(WhatBecameOfTheMove $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway %s', $job->shown())),
        answered: static fn(AMove $move): TheWordCarriedOut => new TheWordCarriedOut(sprintf('answered %s', $move->stance()->value)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(string $because): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met %s', $why->kind()->value)),
    )->said;
}

it('takes each arm it was built on, and no other', function (): void {
    $move = AMove::at(Stance::Applied, TheStandingBeside::of(ThePortsMoved::of(), ''));

    expect(theWordForWhereTheMoveGotTo(WhatBecameOfTheMove::underway(Job::named('j-1'))))->toBe('underway j-1')
        ->and(theWordForWhereTheMoveGotTo(WhatBecameOfTheMove::answered($move)))->toBe('answered applied')
        ->and(theWordForWhereTheMoveGotTo(WhatBecameOfTheMove::ended()))->toBe('ended')
        ->and(theWordForWhereTheMoveGotTo(WhatBecameOfTheMove::refused('Nothing here to take over')))->toBe('refused Nothing here to take over')
        ->and(theWordForWhereTheMoveGotTo(WhatBecameOfTheMove::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met %s', KindOfObstacle::StackDidNotAnswer->value));
});

it('refuses a refusal that does not say why', function (): void {
    expect(static fn(): WhatBecameOfTheMove => WhatBecameOfTheMove::refused(' '))->toThrow(TheMoveSaysNothing::class, '`reason`');
});
