<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Operator\Internal\Presenters\HowTheRunReads;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;

/**
 * Every part of a state, as one row a case can compare.
 *
 * @return array{bool, bool, bool, bool, bool, bool}
 */
function everythingTheRunHolds(TheRunTurnedOutToBe $run): array
{
    return [$run->stands->went->cameBack(), $run->stands->went->isSignedIn, $run->stands->isWorking(), $run->stands->hasEnded(), $run->refused instanceof ARefusalAsShown, $run->answered];
}

it('says each state of a run apart, and only its own', function (): void {
    $reads = new HowTheRunReads();
    $refusal = ARefusalInItsWords::said('The tunnel is already down', '', WhatTheRefusalNamed::nothing(), 'Bring it up first');

    expect(everythingTheRunHolds($reads->notAsked()))->toBe([true, true, false, false, false, false])
        ->and(everythingTheRunHolds($reads->running()))->toBe([true, true, true, false, false, false])
        ->and(everythingTheRunHolds($reads->ended()))->toBe([true, true, false, true, false, false])
        ->and(everythingTheRunHolds($reads->refused($refusal)))->toBe([true, true, false, false, true, false])
        ->and(everythingTheRunHolds($reads->answered()))->toBe([true, true, false, false, false, true])
        ->and(everythingTheRunHolds($reads->signedOut()))->toBe([false, false, false, false, false, false])
        ->and(everythingTheRunHolds($reads->met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe([false, true, false, false, false, false]);
});
