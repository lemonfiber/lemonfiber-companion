<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Health\Api\WhatAStreamSaidSoFar;
use Modules\Kernel\Api\ACopyRunning;
use Modules\Kernel\Api\HowTheCopyMoves;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatIsBeingCopied;
use Modules\Kernel\Api\WhatIsCopied;
use Modules\Operator\Internal\Presenters\HowTheCopyRunningReads;
use Modules\Operator\Internal\ViewModels\ACopyRunningAsShown;

function aCopyRunningAsRead(int $written, int $whole, int $startedSecondsAgo = 7_200): ACopyRunningAsShown
{
    $now = Instant::atEpochSeconds(1_790_000_000);
    $running = new ACopyRunning(WhatIsCopied::Backup, ScopeOfACopy::oneService(ServiceId::called('sonarr')), Instant::atEpochSeconds(1_790_000_000 - $startedSecondsAgo), $written, $whole, 'sonarr database', HowTheCopyMoves::working());

    return new HowTheCopyRunningReads()->of(WhatAStreamSaidSoFar::nothingYet()->after(WhatAStreamSaid::said(WhatIsBeingCopied::running($running)), $now), $now);
}

it('carries how far a copy has got as a share and in whole percent, rounded down', function (): void {
    $read = aCopyRunningAsRead(2, 3);

    expect($read->done)->toBe(2 / 3)
        ->and($read->percent)->toBe(66);
});

it('carries a copy past its whole as all of it, and a copy of nothing as no percent at all', function (): void {
    $past = aCopyRunningAsRead(5, 4);
    $nothing = aCopyRunningAsRead(0, 0);

    expect([$past->done, $past->percent])->toBe([1.0, 100])
        ->and([$nothing->done, $nothing->percent])->toBe([0.0, null]);
});

it('carries what the copy is of and how long ago it started, in the unit that is said', function (): void {
    $read = aCopyRunningAsRead(1, 2);

    expect($read->scope?->said)->toBe('stacks.copy.scope.service')
        ->and($read->scope?->with)->toBe(['name' => 'sonarr'])
        ->and($read->started)->toBe('stacks.copy.running.started.hours')
        ->and($read->since)->toBe(2);
});

it('carries none of it where no copy runs or the app cannot tell', function (): void {
    $now = Instant::atEpochSeconds(1_790_000_000);
    $none = new HowTheCopyRunningReads()->of(WhatAStreamSaidSoFar::nothingYet()->after(WhatAStreamSaid::said(WhatIsBeingCopied::nothing()), $now), $now);
    $unknown = new HowTheCopyRunningReads()->of(WhatAStreamSaidSoFar::nothingYet(), $now);

    foreach ([$none, $unknown] as $read) {
        expect($read->scope)->toBeNull()
            ->and($read->percent)->toBeNull()
            ->and($read->started)->toBe('');
    }
});
