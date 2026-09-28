<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheRestoreRehearsalFound;
use Modules\Kernel\Api\WhatWroteACopy;
use Modules\Kernel\Api\WhereTheDataGoes;

use function sprintf;

/** One line carried out of an arm of a rehearsed restore. */
final readonly class WhichArmTheRehearsedRestoreTook
{
    public function __construct(public string $said) {}
}

/** Which arm a rehearsed restore takes, and what it carried there. */
function whatTheRehearsedRestoreFound(WhatTheRestoreRehearsalFound $found): string
{
    return $found->either(
        listed: static fn(WhatPuttingItBackWouldDo $listing): WhichArmTheRehearsedRestoreTook => new WhichArmTheRehearsedRestoreTook(sprintf('listed:%s', $listing->agreement())),
        refused: static fn(ARefusalInItsWords $why): WhichArmTheRehearsedRestoreTook => new WhichArmTheRehearsedRestoreTook(sprintf('refused:%s', $why->summary())),
        met: static fn(Obstacle $why): WhichArmTheRehearsedRestoreTook => new WhichArmTheRehearsedRestoreTook(sprintf('met:%s', $why->value)),
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
        ->and(whatTheRehearsedRestoreFound(WhatTheRestoreRehearsalFound::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
