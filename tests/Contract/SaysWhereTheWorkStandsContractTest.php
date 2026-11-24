<?php

declare(strict_types=1);

use Modules\Kernel\Api\ExtendingIt;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Operator\Internal\Presenters\HowACopyReads;
use Modules\Operator\Internal\Presenters\HowALandingReads;
use Modules\Operator\Internal\Presenters\HowAMendingReads;
use Modules\Operator\Internal\Presenters\HowAnOfferOfRepairsReads;
use Modules\Operator\Internal\Presenters\HowAResetReads;
use Modules\Operator\Internal\Presenters\HowARunBackReads;
use Modules\Operator\Internal\Presenters\HowASettingChangeReads;
use Modules\Operator\Internal\Presenters\HowExtendingItReads;
use Modules\Operator\Internal\Presenters\HowForgettingReads;
use Modules\Operator\Internal\Presenters\HowPuttingItBackReads;
use Modules\Operator\Internal\Presenters\HowTheLastUpdateReads;
use Modules\Operator\Internal\Presenters\HowTheSwitchReads;
use Modules\Operator\Internal\ViewModels\AResetAsShown;
use Modules\Operator\Internal\ViewModels\HowPuttingARunBackWent;
use Modules\Operator\Internal\ViewModels\HowPuttingItBackWent;
use Modules\Operator\Internal\ViewModels\HowTheCopyWent;
use Modules\Operator\Internal\ViewModels\HowTheLandingWent;
use Modules\Operator\Internal\ViewModels\HowTheLastUpdateWent;
use Modules\Operator\Internal\ViewModels\SaysWhereTheWorkStands;
use Modules\Operator\Internal\ViewModels\TheForgettingTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheSwitchTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatExtendsItTurnedOutToBe;
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
        HowPuttingItBackWent::class => [
            'running' => new HowPuttingItBackReads()->running(),
            'ended' => new HowPuttingItBackReads()->ended(null),
            'met' => new HowPuttingItBackReads()->met($stood),
        ],
        AResetAsShown::class => [
            'running' => HowAResetReads::afterTheYes()->running(),
            'ended' => HowAResetReads::afterTheYes()->ended(null),
            'met' => HowAResetReads::afterTheYes()->met($stood),
        ],
        HowPuttingARunBackWent::class => [
            'running' => new HowARunBackReads()->running(),
            'ended' => new HowARunBackReads()->ended(null),
            'met' => new HowARunBackReads()->met($stood),
        ],
        HowTheLastUpdateWent::class => [
            'running' => new HowTheLastUpdateReads()->running(),
            'ended' => new HowTheLastUpdateReads()->ended(null),
            'met' => new HowTheLastUpdateReads()->met($stood),
        ],
        TheForgettingTurnedOutToBe::class => [
            'running' => new HowForgettingReads()->running(),
            'ended' => new HowForgettingReads()->ended(null),
            'met' => new HowForgettingReads()->met($stood, agreed: false),
        ],
        TheSwitchTurnedOutToBe::class => [
            'running' => new HowTheSwitchReads()->running(),
            'ended' => new HowTheSwitchReads()->ended(null),
            'met' => new HowTheSwitchReads()->met($stood),
        ],
        TheSettingChangeTurnedOutToBe::class => [
            'running' => new HowASettingChangeReads()->running(),
            'ended' => new HowASettingChangeReads()->ended(null),
            'met' => new HowASettingChangeReads()->met($stood),
        ],
        HowTheLandingWent::class => [
            'running' => new HowALandingReads()->waiting('LIBRARY_PATH'),
            'ended' => new HowALandingReads()->ended('LIBRARY_PATH', null),
            'met' => new HowALandingReads()->met('LIBRARY_PATH', $stood),
        ],
        WhatExtendsItTurnedOutToBe::class => [
            'running' => new HowExtendingItReads()->running(ExtendingIt::Install, agreed: false),
            'ended' => new HowExtendingItReads()->ended(null),
            'met' => new HowExtendingItReads()->met($stood),
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
