<?php

declare(strict_types=1);

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Operator\Internal\Presenters\HowACopyReads;
use Modules\Operator\Internal\Presenters\HowAMendingReads;
use Modules\Operator\Internal\Presenters\HowAnOfferOfRepairsReads;
use Modules\Operator\Internal\ViewModels\HowTheCopyWent;
use Modules\Operator\Internal\ViewModels\SaysWhereTheWorkStands;
use Modules\Operator\Internal\ViewModels\WhatTheStackWouldPutRight;
use Modules\Operator\Internal\ViewModels\WhatThisStackPutRight;

// The SaysWhereTheWorkStands contract, run against every report a screen
// following a job draws.
//
// `FollowsAJob` asks after a job again, and hears its lines, only while the
// last report says the work is running, and lets go of it once a report says
// otherwise. So every report has to say running for running work and nothing
// of the kind for work that ended or could not be asked after.

/**
 * Each report, as running work, ended work and work something stood in the way of.
 *
 * @return array<class-string<SaysWhereTheWorkStands>, array{running: SaysWhereTheWorkStands, ended: SaysWhereTheWorkStands, met: SaysWhereTheWorkStands}>
 */
function everyReportOnWork(): array
{
    $stood = Obstacle::of(KindOfObstacle::StackDidNotAnswer);

    return [
        HowTheCopyWent::class => [
            'running' => new HowACopyReads()->running(ScopeOfACopy::theWholeStack()),
            'ended' => new HowACopyReads()->ended(null, ScopeOfACopy::theWholeStack()),
            'met' => new HowACopyReads()->met($stood),
        ],
        WhatTheStackWouldPutRight::class => [
            'running' => new HowAnOfferOfRepairsReads()->stillWorkingItOut(),
            'ended' => new HowAnOfferOfRepairsReads()->ended(null),
            'met' => new HowAnOfferOfRepairsReads()->met($stood),
        ],
        WhatThisStackPutRight::class => [
            'running' => new HowAMendingReads()->stillWorkingItOut(),
            'ended' => new HowAMendingReads()->ended(null),
            'met' => new HowAMendingReads()->met($stood),
        ],
    ];
}

it('says running work is running, and so is asked after again', function (): void {
    foreach (everyReportOnWork() as $which => $reports) {
        expect($reports['running'])->toBeInstanceOf($which)
            ->and($reports['running']->stands->isWorking())->toBeTrue($which);
    }
});

it('says work that ended has ended, and is no longer running', function (): void {
    foreach (everyReportOnWork() as $which => $reports) {
        expect($reports['ended']->stands->isWorking())->toBeFalse($which)
            ->and($reports['ended']->stands->hasEnded())->toBeTrue($which);
    }
});

it('says work something stood in the way of is not running', function (): void {
    foreach (everyReportOnWork() as $which => $reports) {
        expect($reports['met']->stands->isWorking())->toBeFalse($which)
            ->and($reports['met']->stands->went->cameBack())->toBeFalse($which);
    }
});
