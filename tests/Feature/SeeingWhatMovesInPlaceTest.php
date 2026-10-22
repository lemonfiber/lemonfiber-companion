<?php

declare(strict_types=1);

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AMoveInPlace;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheMovesInPlace;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatBecameOfAMoveInPlace;
use Modules\Kernel\Api\WhatBecameOfTheMovesInPlace;
use Modules\Kernel\Api\WhereAServiceEndedUp;
use Modules\Operator\Internal\Presenters\HowAMoveInPlaceReads;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\TheThingScreenOfTheShed;
use Tests\Support\TheUpkeepScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

function jellyfinMovingInPlace(): AMoveInPlace
{
    return AMoveInPlace::of(ServiceId::called('jellyfin'), APluginId::called('plugin-jellyfin'));
}

function sonarrMovingInPlace(): AMoveInPlace
{
    return AMoveInPlace::of(ServiceId::called('sonarr'), APluginId::called('plugin-sonarr'));
}

function radarrMovingInPlace(): AMoveInPlace
{
    return AMoveInPlace::of(ServiceId::called('radarr'), APluginId::called('plugin-radarr'));
}

function aMoveSaid(string $said, AMoveInPlace $move): string
{
    return aLineAboutMoves($said, ['plugin' => $move->plugin()->named(), 'service' => $move->service()->named()]);
}

/** @param array<string, string> $with */
function aLineAboutMoves(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : $key;
}

function anUpdateThatTriedThreeMoves(): HowAgreedWorkIsGoing
{
    return HowAgreedWorkIsGoing::done(Upkeep::reported(
        AgainstThePins::Partial,
        Releases::none(),
        Services::none(),
        Services::none(),
        TheUpkeepScreenOfTheLoft::whatLastNightCameTo(),
        HowTheNotesStand::Current,
        TheStackEdits::none(),
    )->moving(TheMovesInPlace::none(), WhatBecameOfTheMovesInPlace::these(
        WhatBecameOfAMoveInPlace::taken(jellyfinMovingInPlace()),
        WhatBecameOfAMoveInPlace::notTaken(sonarrMovingInPlace(), 'The plugin would not install.'),
        WhatBecameOfAMoveInPlace::notTaken(radarrMovingInPlace(), ''),
    )));
}

it('names each move in place on the update\'s confirmation, apart from the services it changes', function (): void {
    $offered = TheUpkeepScreenOfTheLoft::anEveningWorthSpending()->moving(TheMovesInPlace::these(jellyfinMovingInPlace()), WhatBecameOfTheMovesInPlace::none());
    $screen = TheUpkeepScreenOfTheLoft::theUpkeepScreen(AStackThatKeepsCurrent::with($offered));

    $before = WhatTheDeviceWouldDraw::by($screen)->said();
    $screen->wouldYouLike();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $named = array_search(aMoveSaid(HowAMoveInPlaceReads::WOULD_TAKE, jellyfinMovingInPlace()), $drawn, strict: true);

    expect($before)->not->toContain(aMoveSaid(HowAMoveInPlaceReads::WOULD_TAKE, jellyfinMovingInPlace()))
        ->and(is_int($named))->toBeTrue()
        ->and($drawn[(int) $named - 1])->toBe(__('updates.moves.heading'))
        ->and($screen->wouldChange())->toBe(2)
        ->and($screen->asking()?->movesInPlace())->toHaveCount(1);
});

it('names no move on a confirmation where the stack offered none', function (): void {
    $screen = TheUpkeepScreenOfTheLoft::theUpkeepScreen(AStackThatKeepsCurrent::with(TheUpkeepScreenOfTheLoft::anEveningWorthSpending()));
    $unasked = $screen->wouldMove();
    $screen->wouldYouLike();

    expect($unasked)->toBe([])
        ->and($screen->wouldMove())->toBe([])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->not->toContain(__('updates.moves.heading'));
});

it('reports each move an update tried as taken, or as not taken with why in the stack\'s words', function (): void {
    $screen = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate(AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), anUpdateThatTriedThreeMoves()));
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();
    $sonarr = array_search(aMoveSaid(HowAMoveInPlaceReads::DID_NOT_TAKE, sonarrMovingInPlace()), $drawn, strict: true);
    $radarr = array_search(aMoveSaid(HowAMoveInPlaceReads::DID_NOT_TAKE, radarrMovingInPlace()), $drawn, strict: true);

    expect($drawn)->toContain(__('updates.moves.heading'), aMoveSaid(HowAMoveInPlaceReads::TOOK, jellyfinMovingInPlace()))
        ->and(is_int($sonarr) && is_int($radarr))->toBeTrue()
        ->and($drawn[(int) $sonarr + 1])->toBe('The plugin would not install.')
        ->and($drawn[(int) $radarr + 1])->toBe(__('updates.moves.why_unsaid'));
});

it('reports a move apart from how each service took the update', function (): void {
    $last = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate(AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), anUpdateThatTriedThreeMoves()))->lastUpdate();

    expect(array_map(static fn(object $took): string => $took->service, $last->applied))->toBe(['sonarr', 'jellyfin'])
        ->and($last->didNotArrive)->toBe(1)
        ->and(array_map(static fn(object $move): array => [$move->service, $move->owesAReason, $move->because], $last->moved))->toBe([
            ['jellyfin', false, ''],
            ['sonarr', true, 'The plugin would not install.'],
            ['radarr', true, ''],
        ]);
});

it('reports no move where the update tried none', function (): void {
    $last = TheUpkeepScreenOfTheLoft::aScreenThatTookTheUpdate(AStackThatKeepsCurrent::whichTook(TheUpkeepScreenOfTheLoft::anEveningWorthSpending(), TheUpkeepScreenOfTheLoft::aReportSaying(TheUpkeepScreenOfTheLoft::whatLastNightCameTo())))->lastUpdate();

    expect($last->moved)->toBe([]);
});

it('names each move in place a start names, and says an update takes it', function (): void {
    $started = TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy))
        ->naming(TheMovesInPlace::these(jellyfinMovingInPlace(), sonarrMovingInPlace()));
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done($started))));
    $sonarr = array_search(aMoveSaid(HowAMoveInPlaceReads::ON_OFFER, sonarrMovingInPlace()), $drawn, strict: true);

    expect($drawn)->toContain(__('updates.moves.heading'), aMoveSaid(HowAMoveInPlaceReads::ON_OFFER, jellyfinMovingInPlace()))
        ->and(is_int($sonarr))->toBeTrue()
        ->and($drawn[(int) $sonarr + 1])->toBe(__('updates.moves.an_update_takes_it'))
        ->and(array_count_values($drawn)[aLineAboutMoves('updates.moves.an_update_takes_it')])->toBe(1);
});

it('names no move where a start named none', function (): void {
    $drawn = TheThingScreenOfTheShed::whatStartingSonarrDrew(TheThingScreenOfTheShed::theScreen(TheThingScreenOfTheShed::aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing::done(
        TheThingScreenOfTheShed::aVerbThatRan(WhereAServiceEndedUp::as('Sonarr', HowAServiceRuns::Healthy)),
    ))));

    expect($drawn)->not->toContain(__('updates.moves.heading'), __('updates.moves.an_update_takes_it'));
});

it('says every line about a move in place in every language', function (string $locale): void {
    app()->setLocale($locale);

    foreach ([HowAMoveInPlaceReads::WOULD_TAKE, HowAMoveInPlaceReads::ON_OFFER, HowAMoveInPlaceReads::TOOK, HowAMoveInPlaceReads::DID_NOT_TAKE] as $said) {
        expect(aMoveSaid($said, jellyfinMovingInPlace()))->toContain('plugin-jellyfin', 'jellyfin')
            ->and(aMoveSaid($said, jellyfinMovingInPlace()))->not->toContain(':plugin', ':service');
    }
})->with(['en', 'nl']);
