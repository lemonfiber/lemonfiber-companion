<?php

declare(strict_types=1);

use Modules\Kernel\Api\AClaimant;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFill;
use Modules\Kernel\Api\AFillTurnedDown;
use Modules\Kernel\Api\ALink;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheClaimants;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\Unfilled;
use Modules\Kernel\Api\WhatBecameOfTheFill;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyItWasChosen;
use Modules\Kernel\Api\WhyTheFillWasTurnedDown;
use Modules\Operator\Internal\Screens\HowTheServicesAreWired;
use Modules\Operator\Internal\ViewModels\ALinkAsShown;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatChoosesFillers;
use Tests\Support\Fakes\AStackThatSaysWhatAnswersWhat;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\AStackThatWires;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatAMachineRuns;
use Tests\Support\WhatTheDeviceWouldDraw;

// Choosing which service answers a capability two or more claim, on the
// Connections screen: what the choice would come to, then the yes.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine a filler is chosen on. */
function theStackAFillerIsChosenOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/** What the stack wires to what: a contest, a choice already made, one claimant, and every claimant answering. */
function linksWithAChoiceToMake(): TheLinks
{
    $bundled = static fn(string $service): AClaimant => AClaimant::of(ServiceId::called($service), WhoPutItThere::bundled());
    $asking = static fn(string $by, string $capability, Services $reached, WhatSettledIt $settled, AClaimant ...$claimants): ALink
        => ALink::from(ServiceId::called($by), HowItReaches::asked(Capability::called($capability), $reached, $settled, TheClaimants::these(...$claimants)));

    return TheLinks::of(
        WhatNothingFills::none(),
        $asking('seerr', 'media-server', Services::none(), WhatSettledIt::contested(Services::these(ServiceId::called('plex'), ServiceId::called('jellyfin'))), $bundled('jellyfin'), AClaimant::of(ServiceId::called('plex'), WhoPutItThere::plugin('plex'))),
        $asking('radarr', 'indexer', Services::these(ServiceId::called('prowlarr')), WhatSettledIt::chosen(Services::these(ServiceId::called('jackett')), WhoSettledIt::Operator, WhyItWasChosen::unstated()), $bundled('prowlarr'), $bundled('jackett')),
        $asking('sonarr', 'download-client', Services::these(ServiceId::called('qbittorrent')), WhatSettledIt::outright(), $bundled('qbittorrent')),
        $asking('prowlarr', 'arr', Services::these(ServiceId::called('sonarr'), ServiceId::called('radarr')), WhatSettledIt::each(), $bundled('sonarr'), $bundled('radarr')),
        ALink::from(ServiceId::called('jellyfin'), HowItReaches::byName(ServiceId::called('tdarr'), 'Transcoding runs on the other machine')),
    );
}

/** What choosing plex for media-server would come to, read or made. */
function plexForTheMediaServer(bool $made = false, string $agreement = 'reading-1', ?Services $was = null, ?WhatNothingFills $leaves = null): AFill
{
    $fill = $made ? AFill::made(...) : AFill::read(...);

    return $fill(
        Capability::called('media-server'),
        ServiceId::called('plex'),
        $was ?? Services::these(ServiceId::called('jellyfin')),
        Services::these(ServiceId::called('seerr'), ServiceId::called('bazarr')),
        $leaves ?? WhatNothingFills::these(Unfilled::of(ServiceId::called('tdarr'), Capability::called('transcoder'))),
        '',
        $agreement,
    );
}

/** A choice the stack turned down, for this reason, in words of its own. */
function aFillTurnedDownFor(WhyTheFillWasTurnedDown $why): WhatBecameOfTheFill
{
    return WhatBecameOfTheFill::turnedDown(AFillTurnedDown::because($why, ARefusalInItsWords::said('That choice was not made', 'Since it was read, what fills it now changed.', WhatTheRefusalNamed::nothing())));
}

/** The Connections screen, the links read, asking the fillers given. Named for this file. */
function theScreenChoosingAFiller(AStackThatChoosesFillers $fillers, ?AKeychainInMemory $keychain = null, ?AStackThatSaysWhatAnswersWhat $linking = null): HowTheServicesAreWired
{
    $stack = theStackAFillerIsChosenOn();
    $keychain ??= AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new HowTheServicesAreWired(AStackThatWires::answering(), $linking ?? AStackThatSaysWhatAnswersWhat::with(linksWithAChoiceToMake()), $fillers, AStackThatSupervises::with(WhatAMachineRuns::twoThings()), $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored()]);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    return $screen;
}

it('offers each claimant that does not answer already, only where two or more claim it, in the order the row shows them', function (): void {
    $screen = theScreenChoosingAFiller(AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer())));
    $links = $screen->whatAnswersWhat()->links ?? [];
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(array_map(static fn(ALinkAsShown $link): array => $link->choices, $links))->toBe([['plex', 'jellyfin'], ['jackett'], [], [], []])
        ->and(array_map(static fn(ALinkAsShown $link): string => $link->capability, $links))->toBe(['media-server', 'indexer', 'download-client', 'arr', ''])
        ->and($drawn->offers())->toBe(['plex', 'jellyfin', 'jackett', __('stacks.wiring.wire'), __('health.ask_again')])
        ->and($drawn->said())->toContain(__('stacks.wiring.fills.choose'));
});

it('a tap asks what the choice would come to, writes nothing, and draws all of it before the yes', function (): void {
    $fillers = AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer()));
    $screen = theScreenChoosingAFiller($fillers);
    $screen->choose('media-server', 'plex');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($fillers->asked())->toBe(['would plex answer media-server'])
        ->and($screen->shown?->agreement())->toBe('reading-1')
        ->and($drawn->said())->toContain(
            'media-server',
            __('stacks.wiring.fills.reaches_now', ['service' => 'jellyfin']),
            __('stacks.wiring.fills.would_reach', ['service' => 'plex']),
            __('stacks.wiring.fills.asked_by', ['services' => 'seerr, bazarr']),
            __('stacks.wiring.fills.leaves_unfilled', ['by' => 'tdarr', 'capability' => 'transcoder']),
        )
        ->and($drawn->said())->not->toContain(__('stacks.wiring.fills.leaves_nothing'))
        ->and($drawn->offers())->toContain(__('stacks.wiring.fills.agree'), __('stacks.wiring.fills.never_mind'))
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.fills.close'));
});

it('says so where nothing answers it now and the choice would leave nothing unanswered', function (): void {
    $screen = theScreenChoosingAFiller(AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer(was: Services::none(), leaves: WhatNothingFills::none()))));
    $screen->choose('media-server', 'plex');

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('stacks.wiring.fills.reaches_nothing'), __('stacks.wiring.fills.leaves_nothing'));
});

it('asks nothing for a choice the screen did not offer, or before it has read what answers what', function (): void {
    $fillers = AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer()));
    $screen = theScreenChoosingAFiller($fillers);
    $screen->choose('indexer', 'prowlarr');
    $screen->choose('media-server', 'emby');
    $screen->again();
    $screen->choose('media-server', 'plex');

    expect($fillers->asked())->toBe([])
        ->and($screen->choice)->toBeNull();
});

it('makes the choice drawn, naming its reading and with the reason typed, and reads what answers what again', function (): void {
    $linking = AStackThatSaysWhatAnswersWhat::with(linksWithAChoiceToMake());
    $fillers = AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer()), WhatBecameOfTheFill::fill(plexForTheMediaServer(made: true)));
    $screen = theScreenChoosingAFiller($fillers, linking: $linking);
    $screen->choose('media-server', 'plex');
    $screen->because = 'Plex plays the 4K files';
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($fillers->asked())->toBe(['would plex answer media-server', 'choose plex for media-server, agreeing to reading-1, because "Plex plays the 4K files"'])
        ->and($drawn->said())->toContain(__('stacks.wiring.fills.made', ['service' => 'plex', 'capability' => 'media-server']))
        ->and($drawn->offers())->toContain(__('stacks.wiring.fills.close'))
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.fills.agree'))
        ->and($screen->shown)->toBeNull()
        ->and($screen->because)->toBe('')
        ->and($linking->askings())->toBe(2);
});

it('agrees to nothing where no reading is drawn', function (): void {
    $fillers = AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer(made: true)));
    $screen = theScreenChoosingAFiller($fillers);
    $screen->agree();

    expect($fillers->asked())->toBe([])
        ->and($screen->choice)->toBeNull();
});

it('works a reading that moved out again and draws it with what the stack said moved, waiting on a yes of its own', function (): void {
    $fillers = AStackThatChoosesFillers::answering(
        WhatBecameOfTheFill::fill(plexForTheMediaServer()),
        aFillTurnedDownFor(WhyTheFillWasTurnedDown::Moved),
        WhatBecameOfTheFill::fill(plexForTheMediaServer(agreement: 'reading-2', leaves: WhatNothingFills::none())),
    );
    $screen = theScreenChoosingAFiller($fillers);
    $screen->choose('media-server', 'plex');
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($fillers->asked())->toBe(['would plex answer media-server', 'choose plex for media-server, agreeing to reading-1, because ""', 'would plex answer media-server'])
        ->and($screen->shown?->agreement())->toBe('reading-2')
        ->and($drawn->said())->toContain(__('stacks.wiring.fills.moved'), 'Since it was read, what fills it now changed.', __('stacks.wiring.fills.leaves_nothing'))
        ->and($drawn->offers())->toContain(__('stacks.wiring.fills.agree'));
});

it('draws what the stack said instead where a reading that moved cannot be worked out again', function (): void {
    $fillers = AStackThatChoosesFillers::answering(
        WhatBecameOfTheFill::fill(plexForTheMediaServer()),
        aFillTurnedDownFor(WhyTheFillWasTurnedDown::Moved),
        aFillTurnedDownFor(WhyTheFillWasTurnedDown::AlreadyFills),
    );
    $screen = theScreenChoosingAFiller($fillers);
    $screen->choose('media-server', 'plex');
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->shown)->toBeNull()
        ->and($drawn->said())->toContain(__('stacks.wiring.fills.already'))
        ->and($drawn->said())->not->toContain('Since it was read, what fills it now changed.')
        ->and($drawn->offers())->toContain(__('stacks.wiring.fills.close'))
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.fills.agree'));
});

it('keeps the reading and the reason typed where the reason cannot be kept, so another can be given', function (): void {
    $fillers = AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer()), aFillTurnedDownFor(WhyTheFillWasTurnedDown::ReasonCannotBeKept));
    $screen = theScreenChoosingAFiller($fillers);
    $screen->choose('media-server', 'plex');
    $screen->because = 'far too long';
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->shown?->agreement())->toBe('reading-1')
        ->and($screen->because)->toBe('far too long')
        ->and($drawn->said())->toContain(__('stacks.wiring.fills.reason_cannot_be_kept'), __('stacks.wiring.fills.would_reach', ['service' => 'plex']))
        ->and($drawn->offers())->toContain(__('stacks.wiring.fills.agree'));
});

it('says each other refusal of a choice in words of its own, with the stack\'s beside them, and leaves nothing to agree to', function (WhyTheFillWasTurnedDown $why, string $said, bool $withItsWords): void {
    $screen = theScreenChoosingAFiller(AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer()), aFillTurnedDownFor($why)));
    $screen->choose('media-server', 'plex');
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->shown)->toBeNull()
        ->and($drawn->said())->toContain(__($said))
        ->and(in_array('Since it was read, what fills it now changed.', $drawn->said(), strict: true))->toBe($withItsWords)
        ->and($drawn->offers())->toContain(__('stacks.wiring.fills.close'))
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.fills.agree'));
})->with([
    'no such service' => [WhyTheFillWasTurnedDown::NoSuchService, 'stacks.wiring.fills.no_such_service', true],
    'a service that cannot fill it' => [WhyTheFillWasTurnedDown::CannotFill, 'stacks.wiring.fills.cannot_fill', true],
    'a service that fills it already' => [WhyTheFillWasTurnedDown::AlreadyFills, 'stacks.wiring.fills.already', false],
    'nothing asking for it' => [WhyTheFillWasTurnedDown::NothingAsks, 'stacks.wiring.fills.nothing_asks', true],
    'nowhere to keep it' => [WhyTheFillWasTurnedDown::NowhereToKeepIt, 'stacks.wiring.fills.nowhere_to_keep', true],
]);

it('says a choice the stack refused for a reason of its own was not made, in the stack\'s words', function (): void {
    $refused = WhatBecameOfTheFill::refused(ARefusalInItsWords::said('The record of what is installed cannot be read', 'Nothing was changed.', WhatTheRefusalNamed::nothing()));
    $screen = theScreenChoosingAFiller(AStackThatChoosesFillers::answering($refused));
    $screen->choose('media-server', 'plex');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('stacks.wiring.fills.not_chosen'), 'The record of what is installed cannot be read')
        ->and($drawn->offers())->toContain(__('stacks.wiring.fills.close'));
});

it('draws what stood in the way of a choice, and a refused session signs this device out', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theScreenChoosingAFiller(AStackThatChoosesFillers::answering(WhatBecameOfTheFill::met(Obstacle::of(KindOfObstacle::CredentialWasRefused))), keychain: $keychain);
    $screen->choose('media-server', 'plex');

    expect($screen->choice?->went->isSignedIn)->toBeFalse()
        ->and($keychain->isHolding(theStackAFillerIsChosenOn()->id()))->toBeFalse();
});

it('asks nothing where this device no longer holds a session for the stack', function (): void {
    $keychain = AKeychainInMemory::working();
    $fillers = AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer()));
    $screen = theScreenChoosingAFiller($fillers, keychain: $keychain);
    $keychain->forget(theStackAFillerIsChosenOn()->id());
    $screen->choose('media-server', 'plex');

    expect($fillers->asked())->toBe([])
        ->and($screen->choice?->went->isSignedIn)->toBeFalse();
});

it('puts the choice away unmade, with the reason typed', function (): void {
    $fillers = AStackThatChoosesFillers::answering(WhatBecameOfTheFill::fill(plexForTheMediaServer()));
    $screen = theScreenChoosingAFiller($fillers);
    $screen->choose('media-server', 'plex');
    $screen->because = 'half a thought';
    $screen->letGo();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect([$screen->shown, $screen->choice, $screen->because])->toBe([null, null, ''])
        ->and($fillers->asked())->toBe(['would plex answer media-server'])
        ->and($drawn->offers())->not->toContain(__('stacks.wiring.fills.agree'));
});
