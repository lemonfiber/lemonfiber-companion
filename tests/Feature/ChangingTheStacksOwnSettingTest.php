<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\ThePluginSources;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Screens\WhatTheStackItselfReads;
use Modules\Stacks\Api\AStacksScreen;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatTunesItsSettings;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheDeviceWouldDraw;

// The settings and secrets no installed plugin reads, and changing a setting,
// rehearsed before it is written.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine whose own settings are set. */
function theStackWhoseOwnSettingsAreSet(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The shed'),
        Address::of('https://192.168.1.43:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** What is installed: the egress guard, with its one service. */
function onlyTheGuardInstalled(): ThePlugins
{
    return ThePlugins::listed(
        TheInstalledPlugins::these(APlugin::named('gluetun', '3.40.0', 'Gluetun', WhatVouchesForAPlugin::recorded('gluetun', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these(), ServiceId::called('gluetun'))),
        ThePluginSources::these(),
        TheAnswersOutOfContract::these(),
    );
}

/** These services reading it, each into the variable named after it, declaring no default. */
function readBy(string ...$services): TheServicesReadingIt
{
    return TheServicesReadingIt::these(...array_map(static fn(string $service): AServiceReadingIt => AServiceReadingIt::of(ServiceId::called($service), strtoupper($service), ASettingValue::nothing()), $services));
}

/** The domain the proxy answers on, the core's, set by the operator to this. */
function theDomain(string $set): ASetting
{
    return ASetting::read('domain', WhoseASettingIs::theCores(), readBy('caddy'), ASettingValue::value($set));
}

/** Every setting and secret on the machine: the guard's, which it reads, and the rest, which no plugin reads. */
function everythingTheMachineHolds(string $domain = 'home.example.org'): TheServicesSettings
{
    return TheServicesSettings::of(
        TheSettingsRead::these(
            ASetting::read('vpn.countries', WhoseASettingIs::theNamespaces('gluetun'), readBy('gluetun'), ASettingValue::value('Belgium')),
            theDomain($domain),
            ASetting::read('tls.email', WhoseASettingIs::theNamespaces('caddy'), readBy('caddy'), ASettingValue::nothing()),
            ASetting::read('shared.region', WhoseASettingIs::theCores(), readBy('caddy', 'gluetun'), ASettingValue::nothing()),
        ),
        TheSecretsRead::these(
            ASecret::read('vpn.key', WhoseASettingIs::theNamespaces('gluetun'), readBy('gluetun'), held: true, from: WhereASecretCameFrom::Operator),
            ASecret::read('proxy.token', WhoseASettingIs::theCores(), readBy('caddy'), held: true, from: WhereASecretCameFrom::Minted),
            ASecret::read('spare.key', WhoseASettingIs::theCores(), readBy(), held: false, from: WhereASecretCameFrom::Unsaid),
        ),
    );
}

/** What a change to the domain came to, as the stack reported it. */
function aChangeToTheDomain(WhatTheSettingRunDid $did, ?ASettingChange $change = null): TheSettingAsChanged
{
    $change ??= ASettingChange::toHold('domain', 'lemon.example.org');

    return TheSettingAsChanged::said(
        $change,
        ASettingValue::value('home.example.org'),
        WhatTheChangeReaches::said(Services::these(ServiceId::called('caddy')), Services::these(ServiceId::called('caddy')), '', ''),
        $did,
        AnOffer::named('settings-set:domain:3'),
        everythingTheMachineHolds($did === WhatTheSettingRunDid::Applied ? 'lemon.example.org' : 'home.example.org'),
    );
}

/**
 * A stack listing what the machine holds, rehearsing as `$rehearsal` and, on a yes, saying `$change`.
 *
 * @param WhatBecameOfTheWork<TheSettingAsChanged>|null $rehearsal
 */
function aStackThatSetsItsOwn(?WhatBecameOfTheWork $rehearsal = null): AStackThatTunesItsSettings
{
    return AStackThatTunesItsSettings::answering(
        everythingTheMachineHolds(),
        $rehearsal ?? WhatBecameOfTheWork::answered(aChangeToTheDomain(WhatTheSettingRunDid::Rehearsed)),
        WhatBecameOfTheWork::answered(aChangeToTheDomain(WhatTheSettingRunDid::Applied)),
    );
}

/** The screen of the machine's own settings, with a stack it knows and the guard installed. */
function theStacksOwnSettingsScreen(
    AStackThatTunesItsSettings $tuning,
    ?AStackThatExtendsItself $extending = null,
    bool $signedIn = true,
    ?AStackThatSpeaksUp $hearing = null,
    ?AKeychainInMemory $keychain = null,
): WhatTheStackItselfReads {
    $stack = theStackWhoseOwnSettingsAreSet();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new WhatTheStackItselfReads(
        $tuning,
        $extending ?? AStackThatExtendsItself::listing(onlyTheGuardInstalled()),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening($hearing),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

/** The screen once both its readings came back, a frame apart. */
function theStacksOwnSettingsRead(AStackThatTunesItsSettings $tuning): WhatTheStackItselfReads
{
    $screen = theStacksOwnSettingsScreen($tuning);
    WhatTheDeviceWouldDraw::by($screen);
    WhatTheDeviceWouldDraw::by($screen);

    return $screen;
}

it('reads the plugins on the first frame and the settings on the next, saying it is reading meanwhile', function (): void {
    $tuning = aStackThatSetsItsOwn();
    $screen = theStacksOwnSettingsScreen($tuning);
    $first = WhatTheDeviceWouldDraw::by($screen);

    expect($first->said())->toContain(__('plugins.settings.own_reading'))
        ->and($tuning->askings())->toBe(0)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('domain')
        ->and($tuning->askings())->toBe(1);
});

it('reads the plugins and the settings afresh, a frame apart, when asked again', function (): void {
    $tuning = aStackThatSetsItsOwn();
    $screen = theStacksOwnSettingsRead($tuning);
    $screen->again();

    expect($screen->read)->toBeNull()
        ->and($screen->thePluginsServices)->toBeNull()
        ->and($screen->answered)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('plugins.settings.own_reading'))
        ->and($tuning->askings())->toBe(1)
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('domain')
        ->and($tuning->askings())->toBe(2);
});

it('draws the settings the stream says, without reading them again', function (): void {
    $tuning = aStackThatSetsItsOwn();
    $screen = theStacksOwnSettingsScreen($tuning, hearing: AStackThatSpeaksUp::holdingOpen(WhatWasHeard::aSignOfLife()->settledAs(everythingTheMachineHolds('lemon.example.org'))));

    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->listen();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain('lemon.example.org')
        ->and($drawn)->not->toContain('home.example.org')
        ->and($tuning->askings())->toBe(1);
});

it('draws what it read on every later frame without asking for the session again', function (): void {
    $keychain = AKeychainInMemory::working();
    $screen = theStacksOwnSettingsScreen(aStackThatSetsItsOwn(), keychain: $keychain);
    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $keychain->forget(theStackWhoseOwnSettingsAreSet()->id());

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain('domain');
});

it('lists every setting and secret no installed plugin reads, grouped as the core\'s and by namespace, and nothing a plugin reads', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theStacksOwnSettingsRead(aStackThatSetsItsOwn()));

    expect($drawn->said())->toContain(
        __('navigation.menu.own_settings'),
        __('plugins.settings.own_of'),
        __('plugins.settings.secrets_at_terminal'),
        __('plugins.settings.the_cores'),
        'domain',
        __('plugins.settings.set_by_you'),
        'home.example.org',
        __('plugins.settings.read_by', ['service' => 'caddy', 'variable' => 'CADDY']),
        'proxy.token',
        __('plugins.settings.secret_held'),
        __(WhereASecretCameFrom::Minted->saidOnTheScreen()),
        'spare.key',
        __('plugins.settings.secret_unset'),
        __('plugins.settings.read_by_none'),
        __('plugins.settings.a_namespaces', ['namespace' => 'caddy']),
        'tls.email',
        __('plugins.settings.own_no_secrets'),
    )
        ->and($drawn->said())->not->toContain('vpn.countries', 'Belgium', 'vpn.key', 'shared.region', __('plugins.settings.a_namespaces', ['namespace' => 'gluetun']))
        ->and(array_values(array_filter($drawn->said(), static fn(string $line): bool => $line === __('plugins.settings.the_cores') || $line === __('plugins.settings.a_namespaces', ['namespace' => 'caddy']))))->toBe([__('plugins.settings.the_cores'), __('plugins.settings.a_namespaces', ['namespace' => 'caddy'])])
        ->and($drawn->offers())->toContain(__('plugins.settings.change'), __('plugins.settings.back_to_default'));
});

it('says where every setting and secret is read by an installed plugin', function (): void {
    $tuning = AStackThatTunesItsSettings::answering(
        TheServicesSettings::of(TheSettingsRead::these(ASetting::read('vpn.countries', WhoseASettingIs::theNamespaces('gluetun'), readBy('gluetun'), ASettingValue::nothing())), TheSecretsRead::these()),
        WhatBecameOfTheWork::ended(),
        WhatBecameOfTheWork::ended(),
    );

    expect(WhatTheDeviceWouldDraw::by(theStacksOwnSettingsRead($tuning))->said())->toContain(__('plugins.settings.own_none'));
});

it('opens a setting no plugin reads seeded with its value, and opens nothing for a secret or a setting a plugin reads', function (): void {
    $screen = theStacksOwnSettingsRead(aStackThatSetsItsOwn());
    $screen->change('proxy.token');
    $screen->change('vpn.countries');
    $screen->change('shared.region');

    expect($screen->changing)->toBeNull();

    $screen->change('domain');

    expect($screen->changing?->named)->toBe('domain')
        ->and($screen->typed)->toBe('home.example.org');
});

it('rehearses what is typed, and writes the change the rehearsal described, carrying back its offer', function (): void {
    $tuning = aStackThatSetsItsOwn();
    $screen = theStacksOwnSettingsRead($tuning);
    $screen->change('domain');
    $screen->typed = 'lemon.example.org';
    $screen->rehearse();
    $screen->whileItRuns();
    $rehearsed = WhatTheDeviceWouldDraw::by($screen);

    expect(array_map(static fn(ASettingChange $change): string => sprintf('%s %s=%s', $change->how()->asked(), $change->setting(), $change->value()->said()), $tuning->rehearsed()))->toBe(['settings-set domain=lemon.example.org'])
        ->and($rehearsed->said())->toContain(__(WhatTheSettingRunDid::Rehearsed->saidOnTheScreen()), __('plugins.settings.restarts', ['services' => 'caddy']))
        ->and($rehearsed->offers())->toContain(__('plugins.settings.agree'))
        ->and($tuning->carriedOut())->toBe([]);

    $screen->agree();
    $screen->whileItRuns();

    expect(array_map(static fn(ASettingChangeAgreed $agreed): string => sprintf(
        '%s=%s %s',
        $agreed->change()->setting(),
        $agreed->change()->value()->said(),
        $agreed->offer()->either(named: static fn(string $named): TheWordCarriedOut => new TheWordCarriedOut($named), none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'))->said,
    ), $tuning->carriedOut()))->toBe(['domain=lemon.example.org settings-set:domain:3'])
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__(WhatTheSettingRunDid::Applied->saidOnTheScreen()), 'lemon.example.org');
});

it('rehearses putting a setting no plugin reads back to its default, and nothing for a setting a plugin reads', function (): void {
    $tuning = aStackThatSetsItsOwn(WhatBecameOfTheWork::answered(aChangeToTheDomain(WhatTheSettingRunDid::Rehearsed, ASettingChange::backToDefault('domain'))));
    $screen = theStacksOwnSettingsRead($tuning);
    $screen->backToDefault('vpn.countries');
    $screen->backToDefault('domain');

    expect(array_map(static fn(ASettingChange $change): string => sprintf('%s %s', $change->how()->asked(), $change->setting()), $tuning->rehearsed()))->toBe(['settings-clear domain'])
        ->and($screen->changing?->toDefault)->toBeTrue();
});

it('says what stood in the way of reading the plugins', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theStacksOwnSettingsScreen(aStackThatSetsItsOwn(), AStackThatExtendsItself::met(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork))));

    expect($drawn->said())->toContain(__(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork)->said()))
        ->and($drawn->said())->not->toContain('domain', __('plugins.settings.own_reading'));
});

it('draws a refusal of the change in the stack\'s words, and reads the settings again', function (): void {
    $why = ARefusalInItsWords::said('setting_locked', 'The domain is held by the remote access setup.', WhatTheRefusalNamed::nothing());
    $tuning = aStackThatSetsItsOwn(WhatBecameOfTheWork::refused($why));
    $screen = theStacksOwnSettingsRead($tuning);
    $screen->change('domain');
    $screen->typed = 'lemon.example.org';
    $screen->rehearse();
    $screen->whileItRuns();
    $screen->howItIsGoing();

    expect($screen->read)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('plugins.settings.refused'), 'The domain is held by the remote access setup.');
});

it('asks nothing of a stack this phone holds no session for', function (): void {
    $tuning = aStackThatSetsItsOwn();
    WhatTheDeviceWouldDraw::by(theStacksOwnSettingsScreen($tuning, signedIn: false));

    expect($tuning->askings())->toBe(0);
});

it('is the screen its route names', function (): void {
    expect(AStacksScreen::OwnSettings->forTheStack(theStackWhoseOwnSettingsAreSet()->id()))->toBe(sprintf('/stacks/%s/settings/own', theStackWhoseOwnSettingsAreSet()->id()->stored()));
});
