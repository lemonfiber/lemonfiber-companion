<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWasMended;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/**
 * Which arm following agreed work takes, and what it carried there.
 *
 * @param HowAgreedWorkIsGoing<WhatWasMended> $going
 */
function howAgreedWorkIsGoingReads(HowAgreedWorkIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): TheWordCarriedOut => new TheWordCarriedOut('running'),
        done: static fn(WhatWasMended $mended): TheWordCarriedOut => new TheWordCarriedOut(sprintf('done:%d', $mended->count())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
        moved: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('moved:%s', $why->summary())),
    )->said;
}

it('takes the arm for each state, and carries what each one has', function (): void {
    $moved = ARefusalInItsWords::said('What you agreed to is not what is offered now', '', WhatTheRefusalNamed::nothing());

    expect(howAgreedWorkIsGoingReads(HowAgreedWorkIsGoing::stillRunning()))->toBe('running')
        ->and(howAgreedWorkIsGoingReads(HowAgreedWorkIsGoing::done(WhatWasMended::none())))->toBe('done:0')
        ->and(howAgreedWorkIsGoingReads(HowAgreedWorkIsGoing::ended()))->toBe('ended')
        ->and(howAgreedWorkIsGoingReads(HowAgreedWorkIsGoing::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe(sprintf('met:%s', KindOfObstacle::CredentialWasRefused->value))
        ->and(howAgreedWorkIsGoingReads(HowAgreedWorkIsGoing::moved($moved)))->toBe('moved:What you agreed to is not what is offered now');
});
