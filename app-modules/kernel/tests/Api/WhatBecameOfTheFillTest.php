<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AFill;
use Modules\Kernel\Api\AFillTurnedDown;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhatBecameOfTheFill;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhyTheFillWasTurnedDown;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm answered, as a word. */
function whichArmTheFillTook(WhatBecameOfTheFill $became): string
{
    return $became->either(
        fill: static fn(AFill $fill): TheWordCarriedOut => new TheWordCarriedOut(sprintf('fill: %s', $fill->now()->named())),
        turnedDown: static fn(AFillTurnedDown $down): TheWordCarriedOut => new TheWordCarriedOut(sprintf('turned down: %s, %s', $down->why()->name, $down->said()->summary())),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met: %s', $why->kind()->name)),
    )->said;
}

it('takes a reader to the arm it was built on, with what that arm carries', function (): void {
    $said = ARefusalInItsWords::said('That choice was agreed to against a different reading', '', WhatTheRefusalNamed::nothing());

    expect(whichArmTheFillTook(WhatBecameOfTheFill::fill(AFill::read(Capability::called('media-server'), ServiceId::called('plex'), Services::none(), Services::none(), WhatNothingFills::none(), '', 'cafe0001'))))->toBe('fill: plex')
        ->and(whichArmTheFillTook(WhatBecameOfTheFill::turnedDown(AFillTurnedDown::because(WhyTheFillWasTurnedDown::Moved, $said))))
        ->toBe('turned down: Moved, That choice was agreed to against a different reading')
        ->and(whichArmTheFillTook(WhatBecameOfTheFill::refused(ARefusalInItsWords::said('The stack would not read', '', WhatTheRefusalNamed::nothing()))))->toBe('refused: The stack would not read')
        ->and(whichArmTheFillTook(WhatBecameOfTheFill::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('met: StackDidNotAnswer');
});
