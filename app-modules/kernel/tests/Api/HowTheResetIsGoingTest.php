<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\ConnectionsReverted;
use Modules\Kernel\Api\EditsReverted;
use Modules\Kernel\Api\HowTheResetIsGoing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheReset;
use Modules\Kernel\Api\WhatTheRefusalNamed;

use function sprintf;

/** One line carried out of an arm of a reset being followed. */
final readonly class WhichArmTheResetTook
{
    public function __construct(public string $said) {}
}

/** Which arm following a reset takes, and what it carried there. */
function howTheResetIsGoingReads(HowTheResetIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): WhichArmTheResetTook => new WhichArmTheResetTook('running'),
        done: static fn(TheReset $reset): WhichArmTheResetTook => new WhichArmTheResetTook(sprintf('done:%d', count($reset->connections()))),
        refused: static fn(string $said, WhatTheRefusalNamed $named): WhichArmTheResetTook => new WhichArmTheResetTook(sprintf('refused:%s:%s', $said, $named->forTheOperator())),
        ended: static fn(): WhichArmTheResetTook => new WhichArmTheResetTook('ended'),
        met: static fn(Obstacle $why): WhichArmTheResetTook => new WhichArmTheResetTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('takes the arm for each state, and carries the report, the refusal and the obstacle', function (): void {
    $reset = TheReset::previewed(EditsReverted::these(), ConnectionsReverted::these('sonarr → qbittorrent'));

    expect(howTheResetIsGoingReads(HowTheResetIsGoing::stillRunning()))->toBe('running')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::done($reset)))->toBe('done:1')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::refused('The recorded quality choice could not be read', WhatTheRefusalNamed::as('/srv/lemonfiber/quality.json'))))
        ->toBe('refused:The recorded quality choice could not be read:/srv/lemonfiber/quality.json')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::refused('A file could not be written', WhatTheRefusalNamed::nothing())))
        ->toBe('refused:A file could not be written:')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::ended()))->toBe('ended')
        ->and(howTheResetIsGoingReads(HowTheResetIsGoing::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
