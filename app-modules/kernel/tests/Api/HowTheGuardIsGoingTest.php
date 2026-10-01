<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowTheGuardIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheGuardSaw;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm a guard's standing takes, folded to one line. */
function whichArmAGuardTakes(HowTheGuardIsGoing $going): string
{
    return $going->either(
        guarding: static fn(): TheWordCarriedOut => new TheWordCarriedOut('guarding'),
        sawItGo: static fn(WhatTheGuardSaw $saw): TheWordCarriedOut => new TheWordCarriedOut(sprintf('saw it go:%s', $saw->stoppedThem() ? 'stopped' : 'not stopped')),
        refused: static fn(string $said): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s', $said)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        unknown: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unknown'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('each way a guard stands takes its own arm, and ended and unknown are two', function (): void {
    $saw = WhatTheGuardSaw::of(Forms::these(Form::called('library')), stopped: false, reason: 'The data root is gone.');

    expect(whichArmAGuardTakes(HowTheGuardIsGoing::stillGuarding()))->toBe('guarding')
        ->and(whichArmAGuardTakes(HowTheGuardIsGoing::sawItGo($saw)))->toBe('saw it go:not stopped')
        ->and(whichArmAGuardTakes(HowTheGuardIsGoing::refused('Nothing to guard.')))->toBe('refused:Nothing to guard.')
        ->and(whichArmAGuardTakes(HowTheGuardIsGoing::ended()))->toBe('ended')
        ->and(whichArmAGuardTakes(HowTheGuardIsGoing::unknown()))->toBe('unknown')
        ->and(whichArmAGuardTakes(HowTheGuardIsGoing::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});

it('what the guard saw is carried as the stack said it', function (): void {
    $saw = WhatTheGuardSaw::of(Forms::these(Form::called('library'), Form::called('full')), stopped: true, reason: 'A different volume is mounted.');

    expect($saw->forms()->count())->toBe(2)
        ->and($saw->stoppedThem())->toBeTrue()
        ->and($saw->reason())->toBe('A different volume is mounted.');
});
