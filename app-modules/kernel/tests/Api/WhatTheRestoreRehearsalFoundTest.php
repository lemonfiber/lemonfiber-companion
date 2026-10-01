<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheRestoreRehearsalFound;
use Modules\Kernel\Api\WhatWroteACopy;
use Modules\Kernel\Api\WhereTheDataGoes;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm a rehearsed restore takes, and what it carried there. */
function whatTheRehearsedRestoreFound(WhatTheRestoreRehearsalFound $found): string
{
    return $found->either(
        listed: static fn(WhatPuttingItBackWouldDo $listing): TheWordCarriedOut => new TheWordCarriedOut(sprintf('listed:%s', $listing->agreement())),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s', $why->summary())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('takes the listing arm for a listing, the refusal arm for a refusal and the obstacle arm for an obstacle', function (): void {
    $listing = WhatPuttingItBackWouldDo::listed(
        ACopy::named('lemonfiber-20260924-0300-full'),
        'restore-0f3a',
        ScopeOfACopy::theWholeStack(),
        WhatWroteACopy::of('0.9.0', '2026-09-24T03:00:00Z'),
        WhatACopyHolds::these(),
        older: false,
        data: WhereTheDataGoes::whereItWas(),
    );

    expect(whatTheRehearsedRestoreFound(WhatTheRestoreRehearsalFound::listed($listing)))->toBe('listed:restore-0f3a')
        ->and(whatTheRehearsedRestoreFound(WhatTheRestoreRehearsalFound::refused(ARefusalInItsWords::said('This backup is from a newer lemonfiber', '', WhatTheRefusalNamed::nothing()))))
        ->toBe('refused:This backup is from a newer lemonfiber')
        ->and(whatTheRehearsedRestoreFound(WhatTheRestoreRehearsalFound::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(sprintf('met:%s', KindOfObstacle::StackDidNotAnswer->value));
});
