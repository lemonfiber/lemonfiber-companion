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

/** One line carried out of an arm. */
final readonly class WhereTheMoveGotTo
{
    public function __construct(public string $said) {}
}

/** Which arm an answer took, and what it carried, folded to one line. */
function whereTheMoveGotTo(WhatBecameOfTheMove $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhereTheMoveGotTo => new WhereTheMoveGotTo(sprintf('underway %s', $job->shown())),
        answered: static fn(AMove $move): WhereTheMoveGotTo => new WhereTheMoveGotTo(sprintf('answered %s', $move->stance()->value)),
        ended: static fn(): WhereTheMoveGotTo => new WhereTheMoveGotTo('ended'),
        refused: static fn(string $because): WhereTheMoveGotTo => new WhereTheMoveGotTo(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): WhereTheMoveGotTo => new WhereTheMoveGotTo(sprintf('met %s', $why->kind()->value)),
    )->said;
}

it('takes each arm it was built on, and no other', function (): void {
    $move = AMove::at(Stance::Applied, TheStandingBeside::of(ThePortsMoved::of(), ''));

    expect(whereTheMoveGotTo(WhatBecameOfTheMove::underway(Job::named('j-1'))))->toBe('underway j-1')
        ->and(whereTheMoveGotTo(WhatBecameOfTheMove::answered($move)))->toBe('answered applied')
        ->and(whereTheMoveGotTo(WhatBecameOfTheMove::ended()))->toBe('ended')
        ->and(whereTheMoveGotTo(WhatBecameOfTheMove::refused('Nothing here to take over')))->toBe('refused Nothing here to take over')
        ->and(whereTheMoveGotTo(WhatBecameOfTheMove::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met %s', KindOfObstacle::StackDidNotAnswer->value));
});

it('refuses a refusal that does not say why', function (): void {
    expect(static fn(): WhatBecameOfTheMove => WhatBecameOfTheMove::refused(' '))->toThrow(TheMoveSaysNothing::class, '`reason`');
});
