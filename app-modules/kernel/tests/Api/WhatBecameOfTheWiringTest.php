<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\TheWiringSaysNothing;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Modules\Kernel\Api\WhatIsUnsupported;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm an answer took, and what it carried, folded to one line. */
function theWordForWhereTheWiringGotTo(WhatBecameOfTheWiring $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway %s', $job->shown())),
        answered: static fn(TheWiring $wiring): TheWordCarriedOut => new TheWordCarriedOut(sprintf('answered %s', $wiring->judged()->value)),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(string $because): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met %s', $why->kind()->value)),
    )->said;
}

it('takes each arm it was built on, and no other', function (): void {
    $wiring = TheWiring::written(HowDriftWasJudged::Assessed, WhatIsUnsupported::none());

    expect(theWordForWhereTheWiringGotTo(WhatBecameOfTheWiring::underway(Job::named('j-1'))))->toBe('underway j-1')
        ->and(theWordForWhereTheWiringGotTo(WhatBecameOfTheWiring::answered($wiring)))->toBe('answered assessed')
        ->and(theWordForWhereTheWiringGotTo(WhatBecameOfTheWiring::ended()))->toBe('ended')
        ->and(theWordForWhereTheWiringGotTo(WhatBecameOfTheWiring::refused('Nothing here to wire')))->toBe('refused Nothing here to wire')
        ->and(theWordForWhereTheWiringGotTo(WhatBecameOfTheWiring::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met %s', KindOfObstacle::StackDidNotAnswer->value));
});

it('refuses a refusal that does not say why', function (): void {
    expect(static fn(): WhatBecameOfTheWiring => WhatBecameOfTheWiring::refused(' '))->toThrow(TheWiringSaysNothing::class, '`reason`');
});
