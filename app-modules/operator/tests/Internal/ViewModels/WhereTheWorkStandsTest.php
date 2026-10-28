<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

it('says of each step whether it was asked, is running or ended, and whether the stack could be asked', function (WhereTheWorkStands $stands, array $said): void {
    expect([$stands->went->cameBack(), $stands->wasAsked(), $stands->isWorking(), $stands->hasEnded()])->toBe($said);
})->with([
    'not asked' => [WhereTheWorkStands::notAsked(), [true, false, false, false]],
    'running' => [WhereTheWorkStands::running(), [true, true, true, false]],
    'ended' => [WhereTheWorkStands::ended(), [true, true, false, true]],
    'answered' => [WhereTheWorkStands::answered(), [true, true, false, false]],
    'signed out' => [WhereTheWorkStands::signedOut(), [false, true, false, false]],
    'met' => [WhereTheWorkStands::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), [false, true, false, false]],
]);
