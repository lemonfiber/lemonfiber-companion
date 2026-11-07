<?php

declare(strict_types=1);

namespace Tests\Feature;

use function __;
use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AMoveInPlace;
use Modules\Kernel\Api\AnInstallAStartOffers;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheInstallsAStartOffers;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\WhatBecameOfAMoveInPlace;
use Modules\Kernel\Api\WhatBecameOfTheMovesInPlace;
use Modules\Kernel\Api\WhatElseAVerbSaid;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\WhereAServiceEndedUp;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Presenters\HowAMoveInPlaceReads;
use Modules\Operator\Internal\Presenters\HowAResetReads;
use Modules\Operator\Internal\Presenters\HowASettingIsShown;
use Modules\Sdk\Api\WhatAResetReverts;

use function sprintf;

use Tests\Support\TheThingScreenOfTheShed;
use Tests\Support\WhatAResetSays;
use Tests\Support\WhatTheContractAccepts;

/**
 * A reset report naming a guard it moved.
 *
 * @return array<string, mixed>
 */
function aResetThatMovedAGuard(): array
{
    return WhatAResetSays::envelope(confirmed: true, changed: ['guards' => [['service' => 'decline', 'plugin' => 'plugin-decline', 'taken' => true]]]);
}

function aGuardOf(string $service): AMoveInPlace
{
    return AMoveInPlace::of(ServiceId::called($service), APluginId::called(sprintf('plugin-%s', $service)));
}

it('says what a start came to where it wrote and started nothing for want of a yes, and that a yes is the terminal\'s', function (): void {
    $unstarted = WhatTheVerbCameTo::unstarted(WhetherItWasRehearsed::CarriedOut, WhereTheServicesEndedUp::of(), TheServicesLeftOut::of(), ThePortsHeld::of(), TheStackEdits::these())
        ->saying(WhatElseAVerbSaid::these(
            WhatBecameOfTheMovesInPlace::none(),
            TheInstallsAStartOffers::these(AnInstallAStartOffers::of('plugin-jellyfin', ''), AnInstallAStartOffers::of('plugin-sonarr', 'Its manifest could not be read')),
            PluginLines::none(),
            '',
        ));
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($unstarted))));

    expect($drawn)->toContain(
        __('health.came_to.unstarted'),
        __('health.installs.heading'),
        __('health.installs.at_the_terminal'),
        __('health.installs.would_install', ['plugin' => 'plugin-jellyfin']),
        __('health.installs.would_install', ['plugin' => 'plugin-sonarr']),
        'Its manifest could not be read',
    )->and($drawn)->not->toContain(__('health.came_to.everything_back'));
});

it('says a start with one install on offer came to nothing started, and one that ran beside an install came to what it ran', function (): void {
    $installs = WhatElseAVerbSaid::these(WhatBecameOfTheMovesInPlace::none(), TheInstallsAStartOffers::these(AnInstallAStartOffers::of('plugin-jellyfin', '')), PluginLines::none(), '');
    $drawn = static fn(WhatTheVerbCameTo $report): array => TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($report->saying($installs)))));
    $unstarted = $drawn(WhatTheVerbCameTo::unstarted(WhetherItWasRehearsed::CarriedOut, WhereTheServicesEndedUp::of(), TheServicesLeftOut::of(), ThePortsHeld::of(), TheStackEdits::these()));
    $ran = $drawn(TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy)));

    expect($unstarted)->toContain(__('health.came_to.unstarted'), __('health.installs.would_install', ['plugin' => 'plugin-jellyfin']))
        ->and($ran)->toContain(__('health.installs.would_install', ['plugin' => 'plugin-jellyfin']))
        ->and($ran)->not->toContain(__('health.came_to.unstarted'));
});

it('says which services a start wrote without the render devices, and the stack\'s reason', function (): void {
    $ran = TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy))
        ->saying(WhatElseAVerbSaid::these(WhatBecameOfTheMovesInPlace::none(), TheInstallsAStartOffers::these(), PluginLines::under('unrendered', 'jellyfin runs without hardware transcoding'), 'The host answers no group'));
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($ran))));

    expect($drawn)->toContain(__('health.render.heading'), 'jellyfin runs without hardware transcoding', 'The host answers no group');
});

it('draws no install and no render line where a start named neither', function (): void {
    $ran = TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy));
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($ran))));

    expect($drawn)->not->toContain(__('health.installs.heading'))
        ->and($drawn)->not->toContain(__('health.render.heading'))
        ->and($drawn)->not->toContain(__('updates.guards.heading'));
});

it('says each guard a start moved for good, that it takes no yes, and one it could not move with why', function (): void {
    $ran = TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy))
        ->saying(WhatElseAVerbSaid::these(
            WhatBecameOfTheMovesInPlace::these(WhatBecameOfAMoveInPlace::taken(aGuardOf('decline')), WhatBecameOfAMoveInPlace::notTaken(aGuardOf('gate'), 'It would not install')),
            TheInstallsAStartOffers::these(),
            PluginLines::none(),
            '',
        ));
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($ran))));

    expect($drawn)->toContain(
        __('updates.guards.heading'),
        __('updates.guards.no_yes'),
        __(HowAMoveInPlaceReads::GUARD_MOVED, ['plugin' => 'plugin-decline', 'service' => 'decline']),
        __(HowAMoveInPlaceReads::GUARD_ON_OFFER, ['plugin' => 'plugin-gate', 'service' => 'gate']),
        'It would not install',
    );
});

it('reads a guard not moved with no reason as one a rehearsal would move', function (): void {
    $rows = new HowAMoveInPlaceReads()->guards(WhatBecameOfTheMovesInPlace::these(
        WhatBecameOfAMoveInPlace::taken(aGuardOf('a')),
        WhatBecameOfAMoveInPlace::notTaken(aGuardOf('b'), ''),
        WhatBecameOfAMoveInPlace::notTaken(aGuardOf('c'), 'Because'),
    ));

    expect([$rows[0]->said, $rows[0]->owesAReason, $rows[0]->because])->toBe([HowAMoveInPlaceReads::GUARD_MOVED, false, ''])
        ->and([$rows[1]->said, $rows[1]->owesAReason, $rows[1]->because])->toBe([HowAMoveInPlaceReads::GUARD_WOULD_MOVE, false, ''])
        ->and([$rows[2]->said, $rows[2]->owesAReason, $rows[2]->because])->toBe([HowAMoveInPlaceReads::GUARD_ON_OFFER, true, 'Because']);
});

it('carries each guard a reset moved to what putting the configuration back draws', function (): void {
    $report = aResetThatMovedAGuard();
    $reset = WhatAResetReverts::in(new Envelope(1, 'reset', $report['data']));
    $shown = HowAResetReads::afterTheYes()->reported($reset);

    expect(WhatTheContractAccepts::complaintsAbout('ResetEnvelope', $report))->toBe([])
        ->and($reset->guards())->toHaveCount(1)
        ->and($shown->guards)->toHaveCount(1)
        ->and($shown->guards[0]->service)->toBe('decline');
});

it('marks only the secret a plugin\'s recipes capture, by the name the store keeps it under', function (): void {
    $rows = new HowASettingIsShown(WhoseASettingIs::theCores())->secrets(
        TheSecretsRead::these(
            ASecret::read('tdarr:token', WhoseASettingIs::theNamespaces('tdarr'), TheServicesReadingIt::these(), held: true, from: WhereASecretCameFrom::Recipe),
            ASecret::read('tdarr:other', WhoseASettingIs::theNamespaces('tdarr'), TheServicesReadingIt::these(), held: true, from: WhereASecretCameFrom::Operator),
        ),
        PluginLines::under('captured', 'tdarr:token'),
    );

    expect($rows[0]->captured)->toBeTrue()
        ->and($rows[1]->captured)->toBeFalse()
        ->and(new HowASettingIsShown(WhoseASettingIs::theCores())->secrets(TheSecretsRead::these(ASecret::read('tdarr:token', WhoseASettingIs::theNamespaces('tdarr'), TheServicesReadingIt::these(), held: true, from: WhereASecretCameFrom::Recipe)))[0]->captured)->toBeFalse();
});
