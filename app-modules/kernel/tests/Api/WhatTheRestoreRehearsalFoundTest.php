<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatTheRestoreRehearsalFound;
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
        met: static fn(Obstacle $why): WhichArmTheRehearsedRestoreTook => new WhichArmTheRehearsedRestoreTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('takes the listing arm for a listing and the obstacle arm for an obstacle', function (): void {
    $listing = WhatPuttingItBackWouldDo::listed(
        ACopy::named('lemonfiber-20260924-0300-full'),
        'restore-0f3a',
        ScopeOfACopy::theWholeStack(),
        '0.9.0',
        '2026-09-24T03:00:00Z',
        WhatACopyHolds::these(),
        older: false,
        data: WhereTheDataGoes::whereItWas(),
    );

    expect(whatTheRehearsedRestoreFound(WhatTheRestoreRehearsalFound::listed($listing)))->toBe('listed:restore-0f3a')
        ->and(whatTheRehearsedRestoreFound(WhatTheRestoreRehearsalFound::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
