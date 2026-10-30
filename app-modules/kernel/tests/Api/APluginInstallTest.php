<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\AGrantAsked;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\AnUpdate;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\APluginInstall;
use Modules\Kernel\Api\AProof;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheContestsLeft;
use Modules\Kernel\Api\TheGrantsAsked;
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\ThePluginChanges;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\ThePluginSources;
use Modules\Kernel\Api\TheProofs;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingsItOverrides;
use Modules\Kernel\Api\TheShapesTaken;
use Modules\Kernel\Api\TheUpstreamsWritten;
use Modules\Kernel\Api\TheVersionsItMovesBetween;
use Modules\Kernel\Api\WhatAnInstallChanges;
use Modules\Kernel\Api\WhatAProofCameTo;
use Modules\Kernel\Api\WhatAServiceWaitsFor;
use Modules\Kernel\Api\WhatPuttingTheOldVersionBackCameTo;
use Modules\Kernel\Api\WhatTheChecksMade;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Tests\Support\APluginAsItArrives;

/** An install recorded, with this proof and these checks. */
function anInstallRecorded(WhatAProofCameTo $cameTo, WhatTheChecksMade $checks, bool $recorded = true): APluginInstall
{
    return APluginInstall::reported(
        APluginAsItArrives::held(),
        recorded: $recorded,
        changing: WhatAnInstallChanges::these(ThePluginChanges::these(), TheContestsLeft::these(), TheSettingsItOverrides::these(), TheShapesTaken::these(), TheGrantsAsked::these(), TheUpstreamsWritten::these(), TheServicesWaiting::none()),
        proofs: TheProofs::these(AProof::of('answers', 'It answers', 'GET /', '', $cameTo)),
        checks: $checks,
    );
}

it('is installed only where it was recorded, no proof failed and the checks found nothing broken', function (): void {
    $nothingBroke = WhatTheChecksMade::of(PluginLines::none(), PluginLines::under('unsettled', 'The VPN'));

    expect(anInstallRecorded(WhatAProofCameTo::passed(), $nothingBroke)->held())->toBeTrue()
        ->and(anInstallRecorded(WhatAProofCameTo::unproven('The service did not settle'), $nothingBroke)->held())->toBeTrue()
        ->and(anInstallRecorded(WhatAProofCameTo::failed(PluginLines::under('faults', 'It answered 502')), $nothingBroke)->held())->toBeFalse()
        ->and(anInstallRecorded(WhatAProofCameTo::passed(), WhatTheChecksMade::of(PluginLines::under('broke', 'Sonarr answers'), PluginLines::none()))->held())->toBeFalse()
        ->and(anInstallRecorded(WhatAProofCameTo::passed(), WhatTheChecksMade::notAsked())->held())->toBeFalse()
        ->and(anInstallRecorded(WhatAProofCameTo::passed(), $nothingBroke, recorded: false)->held())->toBeFalse();
});

it('is never installed once it was put back, however its proofs and checks came out', function (): void {
    $putBack = APluginInstall::putBack(
        APluginAsItArrives::held(),
        WhatAnInstallChanges::these(ThePluginChanges::these(), TheContestsLeft::these(), TheSettingsItOverrides::these(), TheShapesTaken::these(), TheGrantsAsked::these(), TheUpstreamsWritten::these(), TheServicesWaiting::none()),
        TheProofs::these(AProof::of('answers', 'It answers', 'GET /', '', WhatAProofCameTo::passed())),
        WhatTheChecksMade::of(PluginLines::none(), PluginLines::none()),
        ARunPutBack::reported(WhetherItWasRehearsed::CarriedOut, WhatWentBack::these(), ChangesAndWhy::these(), ChangesAndWhy::these()),
    );

    expect($putBack->held())->toBeFalse()
        ->and($putBack->isAReading())->toBeFalse();
});

it('is a reading only where nothing was recorded and nothing put back', function (): void {
    $reading = APluginAsItArrives::theReading();
    $putBack = APluginAsItArrives::thePutBack();
    $installed = APluginAsItArrives::theInstall();

    expect($reading->agreement())->toBe(APluginAsItArrives::AGREEMENT)
        ->and($putBack->agreement())->toBe('')
        ->and($installed->agreement())->toBe('')
        ->and(APluginAsItArrives::theListing()->agreement())->toBe('')
        ->and(APluginAsItArrives::theListing()->approvals()->isEmpty())->toBeTrue()
        ->and($reading->approvals()->count())->toBe(1);
});

it('quotes the stack\'s name for a reading as it reads, and none for a run that wrote', function (): void {
    $nothingBroke = WhatTheChecksMade::of(PluginLines::none(), PluginLines::none());
    $about = static fn(bool $recorded): ThePlugins => ThePlugins::aboutAnInstall(TheInstalledPlugins::these(), ThePluginSources::these(), ' install-tdarr-2.1.0 ', anInstallRecorded(WhatAProofCameTo::passed(), $nothingBroke, recorded: $recorded));

    expect($about(recorded: false)->agreement())->toBe('install-tdarr-2.1.0')
        ->and($about(recorded: true)->agreement())->toBe('');
});

it('says the checks were not asked apart from asked and finding nothing', function (): void {
    expect(WhatTheChecksMade::notAsked()->wereAsked())->toBeFalse()
        ->and(WhatTheChecksMade::notAsked()->brokeNothing())->toBeFalse()
        ->and(WhatTheChecksMade::of(PluginLines::none(), PluginLines::none())->brokeNothing())->toBeTrue();
});

it('asks for each shape a service takes apart from the offer, after its recipes\' values, for an install and for an update', function (): void {
    $guarded = APluginAsItArrives::theGuardedInstall();
    $update = ThePlugins::aboutAnUpdate(
        TheInstalledPlugins::these(APluginAsItArrives::held()),
        ThePluginSources::these(),
        APluginAsItArrives::AGREEMENT,
        AnUpdate::reported(
            'tdarr',
            TheVersionsItMovesBetween::of('2.1.0', '2.2.0'),
            PluginLines::under('interrupts', 'tdarr'),
            $guarded,
            ARunPutBack::reported(WhetherItWasRehearsed::Rehearsed, WhatWentBack::these(), ChangesAndWhy::these(), ChangesAndWhy::these()),
            '',
            WhatPuttingTheOldVersionBackCameTo::notNeeded(),
        ),
    );
    $both = [APluginAsItArrives::APPROVAL, APluginAsItArrives::SHAPE_APPROVAL];

    expect(iterator_to_array($guarded->approvals(), preserve_keys: false))->toBe($both)
        ->and(iterator_to_array(APluginAsItArrives::theGuardedReading()->approvals(), preserve_keys: false))->toBe($both)
        ->and(iterator_to_array($update->approvals(), preserve_keys: false))->toBe($both)
        ->and(iterator_to_array(APluginAsItArrives::theGuardedReading()->shapesTaken()->approvals(), preserve_keys: false))->toBe([APluginAsItArrives::SHAPE_APPROVAL])
        ->and(iterator_to_array($update->shapesTaken()->approvals(), preserve_keys: false))->toBe([APluginAsItArrives::SHAPE_APPROVAL])
        ->and(APluginAsItArrives::theListing()->shapesTaken()->isEmpty())->toBeTrue()
        ->and(APluginAsItArrives::theReading()->shapesTaken()->isEmpty())->toBeTrue();
});

it('asks for each password of another service it would hand the plugin apart from the offer, after its recipes\' values', function (): void {
    $asked = AGrantAsked::of(ACredentialGrant::of(APluginId::called('tdarr'), 'plex.token', ServiceId::called('plex'), 'library-scan', AMomentAsWritten::inSeconds('')), APluginAsItArrives::GRANT_APPROVAL);
    $install = APluginInstall::reported(
        APluginAsItArrives::held(),
        recorded: false,
        changing: WhatAnInstallChanges::these(ThePluginChanges::these(), TheContestsLeft::these(), TheSettingsItOverrides::these(), TheShapesTaken::these(), TheGrantsAsked::these($asked), TheUpstreamsWritten::these(), TheServicesWaiting::none()),
        proofs: TheProofs::these(),
        checks: WhatTheChecksMade::notAsked(),
    );

    expect(iterator_to_array($install->approvals(), preserve_keys: false))->toBe([APluginAsItArrives::APPROVAL, APluginAsItArrives::GRANT_APPROVAL])
        ->and(iterator_to_array($install->granting(), preserve_keys: false))->toBe([$asked])
        ->and(iterator_to_array(APluginAsItArrives::theGrantingReading()->approvals(), preserve_keys: false))->toBe([APluginAsItArrives::APPROVAL, APluginAsItArrives::GRANT_APPROVAL]);
});

it('says what securing its egress guard came to in the stack\'s words, and nothing where nothing was said', function (): void {
    $changing = WhatAnInstallChanges::these(ThePluginChanges::these(), TheContestsLeft::these(), TheSettingsItOverrides::these(), TheShapesTaken::these(), TheGrantsAsked::these(), TheUpstreamsWritten::these(), TheServicesWaiting::none());
    $checks = WhatTheChecksMade::of(PluginLines::none(), PluginLines::none());
    $putBack = ARunPutBack::reported(WhetherItWasRehearsed::CarriedOut, WhatWentBack::these(), ChangesAndWhy::these(), ChangesAndWhy::these());

    expect(anInstallRecorded(WhatAProofCameTo::passed(), $checks)->secured())->toBe('')
        ->and(APluginInstall::reported(APluginAsItArrives::held(), recorded: true, changing: $changing, proofs: TheProofs::these(), checks: $checks, secured: ' gluetun, restarted to take it up ')->secured())->toBe('gluetun, restarted to take it up')
        ->and(APluginInstall::putBack(APluginAsItArrives::held(), $changing, TheProofs::these(), $checks, $putBack)->secured())->toBe('')
        ->and(APluginInstall::putBack(APluginAsItArrives::held(), $changing, TheProofs::these(), $checks, $putBack, ' the guard did not take it up ')->secured())->toBe('the guard did not take it up');
});

it('names every service it would leave held back', function (): void {
    $waiting = TheServicesWaiting::these(AServiceWaiting::for(ServiceId::called('gluetun'), WhatAServiceWaitsFor::aSecret('vpn.key')));
    $install = APluginInstall::reported(
        APluginAsItArrives::held(),
        recorded: false,
        changing: WhatAnInstallChanges::these(ThePluginChanges::these(), TheContestsLeft::these(), TheSettingsItOverrides::these(), TheShapesTaken::these(), TheGrantsAsked::these(), TheUpstreamsWritten::these(), $waiting),
        proofs: TheProofs::these(),
        checks: WhatTheChecksMade::notAsked(),
    );

    expect($install->waiting())->toBe($waiting);
});
