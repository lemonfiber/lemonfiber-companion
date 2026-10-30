<?php

declare(strict_types=1);

use Modules\Kernel\Api\AChoice;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\AServiceWaiting;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheChoices;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatAChoiceNeeds;
use Modules\Kernel\Api\WhatAServiceWaitsFor;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Screens\WhatThisServiceWaitsFor;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\ASettingChangeAsReported;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatTunesItsSettings;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

/** The machine holding the guard back. */
function theMachineHoldingTheGuardBack(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** A setting gluetun reads, holding nothing yet, among these where any are given. */
function aSettingTheGuardReads(string $name, AChoice ...$choices): ASetting
{
    return ASetting::read($name, WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('gluetun'), 'VARIABLE', ASettingValue::nothing())), ASettingValue::nothing())
        ->among(TheChoices::these(...$choices));
}

/**
 * The guard held back on its provider and its key, its protocol hanging on the provider, and a setting apart from all of it.
 *
 * Qbittorrent waits on the guard.
 */
function theGuardHeldBack(): TheServicesSettings
{
    return TheServicesSettings::of(
        TheSettingsRead::these(
            aSettingTheGuardReads('vpn.provider', AChoice::of('mullvad', WhatAChoiceNeeds::nothing(), open: true), AChoice::of('pia', WhatAChoiceNeeds::nothing(), open: true)),
            aSettingTheGuardReads('vpn.type', AChoice::of('wireguard', WhatAChoiceNeeds::holding('vpn.provider', 'mullvad'), open: false)),
            aSettingTheGuardReads('domain'),
        ),
        TheSecretsRead::these(),
    )->holdingBack(TheServicesWaiting::these(
        AServiceWaiting::for(ServiceId::called('gluetun'), WhatAServiceWaitsFor::aSetting('vpn.provider', 'mullvad', 'pia'), WhatAServiceWaitsFor::aSecret('vpn.key')),
        AServiceWaiting::for(ServiceId::called('qbittorrent'), WhatAServiceWaitsFor::aService(ServiceId::called('gluetun'))),
    ));
}

/** A stack listing the guard held back, refusing every change because the provider moves the protocol out of its choices. */
function aStackHoldingTheGuardBack(): AStackThatTunesItsSettings
{
    $refused = WhatBecameOfTheWork::refused(ARefusalInItsWords::said('vpn.type cannot hold its value; its choices are wireguard', '', WhatTheRefusalNamed::nothing(), ''));

    return AStackThatTunesItsSettings::answering(theGuardHeldBack(), $refused, $refused);
}

/** The page about one service the stack holds back, on a phone signed in to it or not. */
function thePageAboutWhatItWaitsFor(AStackThatTunesItsSettings $tuning, string $service = 'gluetun', bool $signedIn = true): WhatThisServiceWaitsFor
{
    $stack = theMachineHoldingTheGuardBack();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatThisServiceWaitsFor($tuning, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening());
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $service]);

    return $screen;
}

it('is registered under the route the services page sends it to', function (): void {
    $resolved = NativeRouter::resolve(AStacksScreen::Waiting->forTheStacksService(theMachineHoldingTheGuardBack()->id(), ServiceId::called('gluetun')));

    expect($resolved['class'] ?? null)->toBe(WhatThisServiceWaitsFor::class);
});

it('draws everything the service waits for, never by value, and only the settings among it to change', function (): void {
    $tuning = aStackHoldingTheGuardBack();
    $screen = thePageAboutWhatItWaitsFor($tuning);
    WhatTheDeviceWouldDraw::by($screen);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(
        __('plugins.waiting.held_back', ['service' => 'gluetun']),
        __('plugins.waiting.choice', ['setting' => 'vpn.provider', 'choices' => 'mullvad, pia']),
        __('plugins.waiting.secret', ['secret' => 'vpn.key']),
        'vpn.provider',
    )
        ->and($drawn->said())->not->toContain('vpn.type', 'domain')
        ->and($drawn->offers())->toContain(__('plugins.settings.change'))
        ->and($tuning->askings())->toBe(1);
});

it('draws the service a service runs through as waiting itself, and offers no setting for it', function (): void {
    $screen = thePageAboutWhatItWaitsFor(aStackHoldingTheGuardBack(), 'qbittorrent');
    WhatTheDeviceWouldDraw::by($screen);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('plugins.waiting.held_back', ['service' => 'qbittorrent']), __('plugins.waiting.service', ['service' => 'gluetun']))
        ->and($drawn->offers())->not->toContain(__('plugins.settings.change'));
});

it('says a service the stack no longer holds back waits for nothing, with the way to the services', function (): void {
    $screen = thePageAboutWhatItWaitsFor(aStackHoldingTheGuardBack(), 'caddy');
    WhatTheDeviceWouldDraw::by($screen);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('plugins.waiting.not_held_back', ['service' => 'caddy']))
        ->and($drawn->offers())->toContain(__('navigation.services'));
});

it('changes a setting the service waits for, and after a refusal offers the one hanging on it in the same change', function (): void {
    $tuning = aStackHoldingTheGuardBack();
    $screen = thePageAboutWhatItWaitsFor($tuning);
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->change('domain');
    $apart = $screen->changing;
    $screen->change('vpn.provider');
    $screen->choose('vpn.provider', 'pia');
    $screen->rehearse();
    $screen->whileItRuns();
    $refused = WhatTheDeviceWouldDraw::by($screen);
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $screen->alsoMove('vpn.type');
    $screen->choose('vpn.type', 'wireguard');
    $screen->rehearse();

    expect($apart)->toBeNull()
        ->and($refused->said())->toContain('vpn.type cannot hold its value; its choices are wireguard')
        ->and($screen->toMoveAlongside())->toBe([])
        ->and($drawn->offers())->toContain(__('plugins.settings.also_move', ['setting' => 'vpn.type']))
        ->and(array_map(ASettingChangeAsReported::said(...), $tuning->rehearsed()))->toBe(['settings-set vpn.provider=pia', 'settings-set vpn.provider=pia vpn.type=wireguard']);
});

it('draws the settings moved alongside the one the service waits for', function (): void {
    $tuning = aStackHoldingTheGuardBack();
    $screen = thePageAboutWhatItWaitsFor($tuning);
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->change('vpn.provider');
    $screen->choose('vpn.provider', 'mullvad');
    $screen->rehearse();
    $screen->whileItRuns();
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->alsoMove('vpn.type');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__('plugins.settings.alongside', ['setting' => 'vpn.provider']), 'vpn.type');
});

it('asks nothing of a phone holding no session, and draws what stood in the way of reading', function (): void {
    $tuning = aStackHoldingTheGuardBack();
    $signedOut = thePageAboutWhatItWaitsFor($tuning, signedIn: false);
    $unreached = thePageAboutWhatItWaitsFor(AStackThatTunesItsSettings::sayingAtOnce(WhatWasFoundOfTheSettings::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), WhatBecameOfTheWork::ended()));
    WhatTheDeviceWouldDraw::by($unreached);

    expect($signedOut->answer()->went->isSignedIn)->toBeFalse()
        ->and($tuning->askings())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($unreached)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()));
});

it('reads the settings again when asked', function (): void {
    $tuning = aStackHoldingTheGuardBack();
    $screen = thePageAboutWhatItWaitsFor($tuning);
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);

    expect($tuning->askings())->toBe(2);
});
