<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ConnectionsReverted;
use Modules\Kernel\Api\HowTheResetIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheReset;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\WhatTheRefusalNamed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm following a reset takes, and what it carried there. */
function howTheResetIsGoingReads(HowTheResetIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('running'),
        done: static fn(TheReset $reset): TheWordCarriedOut => new TheWordCarriedOut(sprintf('done:%d', count($reset->connections()))),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s:%s', $why->summary(), $why->named()->forTheOperator())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the arm for each state, and carries the report, the refusal and the obstacle', function (): void {
    $reset = TheReset::previewed(TheStackEdits::these(), ConnectionsReverted::these('sonarr → qbittorrent'));

    expect(howTheResetIsGoingReads(HowTheResetIsGoing::stillRunning()))->toBe('running')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::done($reset)))->toBe('done:1')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::refused(ARefusalInItsWords::said('The recorded quality choice could not be read', '', WhatTheRefusalNamed::as('/srv/lemonfiber/quality.json')))))
        ->toBe('refused:The recorded quality choice could not be read:/srv/lemonfiber/quality.json')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::refused(ARefusalInItsWords::said('A file could not be written', '', WhatTheRefusalNamed::nothing()))))
        ->toBe('refused:A file could not be written:')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::ended()))->toBe('ended')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});
