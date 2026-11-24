<?php

declare(strict_types=1);

use Modules\Kernel\Api\ExtendingIt;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Operator\Internal\Presenters\HowABundleReads;
use Modules\Operator\Internal\Presenters\HowACopyReads;
use Modules\Operator\Internal\Presenters\HowACredentialRevokeReads;
use Modules\Operator\Internal\Presenters\HowAGuardReads;
use Modules\Operator\Internal\Presenters\HowALandingReads;
use Modules\Operator\Internal\Presenters\HowAMendingReads;
use Modules\Operator\Internal\Presenters\HowAMoveReads;
use Modules\Operator\Internal\Presenters\HowAnOfferOfRepairsReads;
use Modules\Operator\Internal\Presenters\HowAReplacementReads;
use Modules\Operator\Internal\Presenters\HowAResetReads;
use Modules\Operator\Internal\Presenters\HowARunBackReads;
use Modules\Operator\Internal\Presenters\HowASettingChangeReads;
use Modules\Operator\Internal\Presenters\HowExtendingItReads;
use Modules\Operator\Internal\Presenters\HowForgettingReads;
use Modules\Operator\Internal\Presenters\HowGivingRoomBackReads;
use Modules\Operator\Internal\Presenters\HowLettingItGoReads;
use Modules\Operator\Internal\Presenters\HowPausingReads;
use Modules\Operator\Internal\Presenters\HowPuttingItBackReads;
use Modules\Operator\Internal\Presenters\HowTakingItOffReads;
use Modules\Operator\Internal\Presenters\HowTheAcceptanceReads;
use Modules\Operator\Internal\Presenters\HowTheAllowanceChangeReads;
use Modules\Operator\Internal\Presenters\HowTheHandoffReads;
use Modules\Operator\Internal\Presenters\HowTheInvitationReads;
use Modules\Operator\Internal\Presenters\HowTheLastUpdateReads;
use Modules\Operator\Internal\Presenters\HowTheLineChangeReads;
use Modules\Operator\Internal\Presenters\HowThePairingCodeReads;
use Modules\Operator\Internal\Presenters\HowTheRemovalReads;
use Modules\Operator\Internal\Presenters\HowTheRunReads;
use Modules\Operator\Internal\Presenters\HowTheSwitchReads;
use Modules\Operator\Internal\Presenters\HowTheWiringReads;
use Modules\Operator\Internal\ViewModels\AResetAsShown;
use Modules\Operator\Internal\ViewModels\HowGivingItBackWent;
use Modules\Operator\Internal\ViewModels\HowLettingItGoWent;
use Modules\Operator\Internal\ViewModels\HowPausingWent;
use Modules\Operator\Internal\ViewModels\HowPuttingARunBackWent;
use Modules\Operator\Internal\ViewModels\HowPuttingItBackWent;
use Modules\Operator\Internal\ViewModels\HowTheBundleWent;
use Modules\Operator\Internal\ViewModels\HowTheCopyWent;
use Modules\Operator\Internal\ViewModels\HowTheGuardWent;
use Modules\Operator\Internal\ViewModels\HowTheHandoffWent;
use Modules\Operator\Internal\ViewModels\HowTheLandingWent;
use Modules\Operator\Internal\ViewModels\HowTheLastUpdateWent;
use Modules\Operator\Internal\ViewModels\HowThePairingCodeWent;
use Modules\Operator\Internal\ViewModels\SaysWhereTheWorkStands;
use Modules\Operator\Internal\ViewModels\TakingItOffTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheAcceptanceTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheAllowanceChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheCredentialRevokeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheForgettingTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheInvitationTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheLineChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheMoveTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheRemovalTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheReplacementTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheSwitchTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheWiringTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatExtendsItTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatLettingItGoWouldShow;
use Modules\Operator\Internal\ViewModels\WhatPausingWouldShow;
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
 * A report with no word of its own for ended work draws the screen as before it asked, so it has no `ended`.
 *
 * @return array<class-string<SaysWhereTheWorkStands>, array{running: SaysWhereTheWorkStands, ended?: SaysWhereTheWorkStands, met: SaysWhereTheWorkStands}>
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
        TheAcceptanceTurnedOutToBe::class => [
            'running' => new HowTheAcceptanceReads()->running(),
            'ended' => new HowTheAcceptanceReads()->ended(null),
            'met' => new HowTheAcceptanceReads()->met($stood),
        ],
        HowTheHandoffWent::class => [
            'running' => new HowTheHandoffReads()->working(),
            'met' => new HowTheHandoffReads()->met($stood),
        ],
        HowThePairingCodeWent::class => [
            'running' => new HowThePairingCodeReads()->working(),
            'met' => new HowThePairingCodeReads()->met($stood),
        ],
        TheInvitationTurnedOutToBe::class => [
            'running' => new HowTheInvitationReads()->running('Sam'),
            'ended' => new HowTheInvitationReads()->ended(null, 'Sam'),
            'met' => new HowTheInvitationReads()->met($stood, 'Sam'),
        ],
        TheRemovalTurnedOutToBe::class => [
            'running' => new HowTheRemovalReads()->running('anna', agreed: true),
            'ended' => new HowTheRemovalReads()->ended(null, 'anna', agreed: true),
            'met' => new HowTheRemovalReads()->met($stood, 'anna', agreed: true),
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
        TheAllowanceChangeTurnedOutToBe::class => [
            'running' => new HowTheAllowanceChangeReads()->running(),
            'ended' => new HowTheAllowanceChangeReads()->ended(null),
            'met' => new HowTheAllowanceChangeReads()->met($stood),
        ],
        TheCredentialRevokeTurnedOutToBe::class => [
            'running' => new HowACredentialRevokeReads()->running(),
            'ended' => new HowACredentialRevokeReads()->ended(null),
            'met' => new HowACredentialRevokeReads()->met($stood),
        ],
        TheReplacementTurnedOutToBe::class => [
            'running' => new HowAReplacementReads()->running(),
            'ended' => new HowAReplacementReads()->ended(null),
            'met' => new HowAReplacementReads()->met($stood),
        ],
        TheLineChangeTurnedOutToBe::class => [
            'running' => new HowTheLineChangeReads()->running(),
            'ended' => new HowTheLineChangeReads()->ended(null),
            'met' => new HowTheLineChangeReads()->met($stood),
        ],
        HowGivingItBackWent::class => [
            'running' => new HowGivingRoomBackReads()->running(),
            'ended' => new HowGivingRoomBackReads()->ended(null),
            'met' => new HowGivingRoomBackReads()->met($stood),
        ],
        WhatLettingItGoWouldShow::class => [
            'running' => new HowLettingItGoReads()->stillWorkingItOut(),
            'ended' => new HowLettingItGoReads()->offerEnded(null),
            'met' => new HowLettingItGoReads()->notOffered($stood),
        ],
        HowLettingItGoWent::class => [
            'running' => new HowLettingItGoReads()->running(),
            'ended' => new HowLettingItGoReads()->ended(null),
            'met' => new HowLettingItGoReads()->met($stood),
        ],
        WhatPausingWouldShow::class => [
            'running' => new HowPausingReads()->stillWorkingItOut(),
            'ended' => new HowPausingReads()->offerEnded(null),
            'met' => new HowPausingReads()->notOffered($stood),
        ],
        HowPausingWent::class => [
            'running' => new HowPausingReads()->running(),
            'ended' => new HowPausingReads()->ended(null),
            'met' => new HowPausingReads()->met($stood),
        ],
        HowTheGuardWent::class => [
            'running' => new HowAGuardReads()->guarding(Forms::these(Form::called('jellyfin'))),
            'met' => new HowAGuardReads()->met($stood),
        ],
        TheWiringTurnedOutToBe::class => [
            'running' => new HowTheWiringReads()->running(),
            'ended' => new HowTheWiringReads()->ended(null),
            'met' => new HowTheWiringReads()->met($stood),
        ],
        TheMoveTurnedOutToBe::class => [
            'running' => new HowAMoveReads()->running('adopt'),
            'ended' => new HowAMoveReads()->ended(null, 'adopt'),
            'met' => new HowAMoveReads()->met($stood, 'adopt'),
        ],
        TakingItOffTurnedOutToBe::class => [
            'running' => new HowTakingItOffReads()->running(endsThisSession: false),
            'ended' => new HowTakingItOffReads()->ended(null, endsThisSession: false),
            'met' => new HowTakingItOffReads()->met($stood),
        ],
        HowTheBundleWent::class => [
            'running' => new HowABundleReads()->running(),
            'ended' => new HowABundleReads()->ended(null),
            'met' => new HowABundleReads()->met($stood),
        ],
        TheRunTurnedOutToBe::class => [
            'running' => new HowTheRunReads()->running(),
            'ended' => new HowTheRunReads()->ended(null),
            'met' => new HowTheRunReads()->met($stood),
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
        if (array_key_exists('ended', $reports)) {
            expect($reports['ended']->stands->isWorking())->toBeFalse($which)
                ->and($reports['ended']->stands->hasEnded())->toBeTrue($which);
        }
    }
});

it('says work something stood in the way of is not running', function (): void {
    foreach (everyReportOnWork() as $which => $reports) {
        expect($reports['met']->stands->isWorking())->toBeFalse($which)
            ->and($reports['met']->stands->went->cameBack())->toBeFalse($which);
    }
});
