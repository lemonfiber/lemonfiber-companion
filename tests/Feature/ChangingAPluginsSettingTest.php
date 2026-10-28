<?php

declare(strict_types=1);

use Modules\Dx\Providers\DxServiceProvider;
use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingWhatServicesRead;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheCredentialGrants;
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
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\Screens\HowThisPluginIsSet;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\Fakes\AStackThatTunesItsSettings;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheDeviceWouldDraw;

// One plugin's settings and secrets, and changing a setting, rehearsed before it is written.
//
// Here rather than in the operator module's own tests because a screen renders,
// and rendering needs the application.

/** The machine whose plugin is set. */
/** When the grant below was approved. */
const WHEN_THE_GUARD_WAS_GRANTED = 1_761_321_600;

function theStackWhosePluginIsSet(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('b', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

/** The egress guard, installed with its one service. */
function theGuardPlugin(): APlugin
{
    return APlugin::named('gluetun', '3.40.0', 'Gluetun', WhatVouchesForAPlugin::recorded('gluetun', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these(), ServiceId::called('gluetun'));
}

/** What is installed: the guard. */
function theGuardInstalled(): ThePlugins
{
    return ThePlugins::listed(TheInstalledPlugins::these(theGuardPlugin()), ThePluginSources::these(), TheAnswersOutOfContract::these());
}

/** One service reading a setting into one variable, declaring no default. */
function readInto(string $service, string $variable): AServiceReadingIt
{
    return AServiceReadingIt::of(ServiceId::called($service), $variable, ASettingValue::nothing());
}

/** The countries the guard may exit from, set by the operator to these. */
function theCountries(string $set): ASetting
{
    return ASetting::read('vpn.countries', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('gluetun'), 'SERVER_COUNTRIES', ASettingValue::value('Netherlands'))), ASettingValue::value($set));
}

/** Every setting and secret the machine's services read, the guard's among them, the countries set to these. */
function theMachinesSettings(string $countries = 'Belgium'): TheServicesSettings
{
    return TheServicesSettings::of(
        TheSettingsRead::these(
            theCountries($countries),
            ASetting::read('domain', WhoseASettingIs::theCores(), TheServicesReadingIt::these(readInto('caddy', 'DOMAIN'), readInto('gluetun', 'PUBLIC_HOST')), ASettingValue::nothing()),
            ASetting::read('vpn.provider', WhoseASettingIs::theNamespaces('vpn-pack'), TheServicesReadingIt::these(readInto('gluetun', 'VPN_SERVICE_PROVIDER')), ASettingValue::nothing()),
            ASetting::read('tls.email', WhoseASettingIs::theNamespaces('caddy'), TheServicesReadingIt::these(readInto('caddy', 'EMAIL')), ASettingValue::value('ops@example.org')),
        ),
        TheSecretsRead::these(
            ASecret::read('vpn.key', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(readInto('gluetun', 'WIREGUARD_PRIVATE_KEY')), held: true, from: WhereASecretCameFrom::Operator),
            ASecret::read('vpn.user', WhoseASettingIs::theCores(), TheServicesReadingIt::these(readInto('gluetun', 'OPENVPN_USER')), held: false, from: WhereASecretCameFrom::Unsaid),
            ASecret::read('proxy.token', WhoseASettingIs::theCores(), TheServicesReadingIt::these(readInto('caddy', 'TOKEN')), held: true, from: WhereASecretCameFrom::Recipe),
        ),
    );
}

/** What a change to the countries came to, as the stack reported it. */
function aChangeToTheCountries(WhatTheSettingRunDid $did, ?ASettingChange $change = null, string $consequence = '', string $unrestarted = '', ?Services $restarts = null): TheSettingAsChanged
{
    $change ??= ASettingChange::toHold('vpn.countries', 'Germany');

    return TheSettingAsChanged::said(
        $change,
        ASettingValue::value('Belgium'),
        WhatTheChangeReaches::said(Services::these(ServiceId::called('gluetun')), $restarts ?? Services::these(ServiceId::called('gluetun'), ServiceId::called('qbittorrent')), $consequence, $unrestarted),
        $did,
        AnOffer::named('settings-set:vpn.countries:7'),
        theMachinesSettings($did === WhatTheSettingRunDid::Applied ? 'Germany' : 'Belgium'),
    );
}

/**
 * A stack listing the machine's settings, rehearsing as `$rehearsal` and, on a yes, saying `$change`.
 *
 * @param WhatBecameOfTheWork<TheSettingAsChanged>|null $rehearsal
 * @param WhatBecameOfTheWork<TheSettingAsChanged>|null $change
 */
function aStackThatSetsTheGuard(?WhatBecameOfTheWork $rehearsal = null, ?WhatBecameOfTheWork $change = null): AStackThatTunesItsSettings
{
    return AStackThatTunesItsSettings::answering(
        theMachinesSettings(),
        $rehearsal ?? WhatBecameOfTheWork::answered(aChangeToTheCountries(WhatTheSettingRunDid::Rehearsed)),
        $change ?? WhatBecameOfTheWork::answered(aChangeToTheCountries(WhatTheSettingRunDid::Applied)),
    );
}

/** The screen about one plugin, with a stack it knows and a keychain holding whatever a case says. */
function theGuardsSettingsScreen(
    AStackThatTunesItsSettings $tuning,
    ?AStackThatExtendsItself $extending = null,
    bool $signedIn = true,
    string $plugin = 'gluetun',
    ?AStackThatSpeaksUp $hearing = null,
    ?AKeychainInMemory $keychain = null,
): HowThisPluginIsSet {
    $stack = theStackWhosePluginIsSet();
    $keychain ??= AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new HowThisPluginIsSet(
        $tuning,
        $extending ?? AStackThatExtendsItself::listing(theGuardInstalled()),
        FrozenClock::at(Instant::atEpochSeconds(WHEN_THE_GUARD_WAS_GRANTED + 3_600)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening($hearing),
    );
    $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $plugin]);

    return $screen;
}

/** The screen once a rehearsal of Germany has come back. */
function aRehearsalOfGermany(AStackThatTunesItsSettings $tuning): HowThisPluginIsSet
{
    $screen = theGuardsSettingsScreen($tuning);
    $screen->answer();
    $screen->change('vpn.countries');
    $screen->typed = 'Germany';
    $screen->rehearse();
    $screen->whileItRuns();
    $screen->howItIsGoing();

    return $screen;
}

it('lists every setting and secret one of the plugin\'s services reads, and nothing only another service reads', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theGuardsSettingsScreen(aStackThatSetsTheGuard()));

    expect($drawn->said())->toContain(
        __('plugins.settings.heading'),
        __('plugins.settings.of', ['name' => 'Gluetun']),
        'vpn.countries',
        __('plugins.settings.set_by_you'),
        'Belgium',
        __('plugins.settings.read_by_default', ['service' => 'gluetun', 'variable' => 'SERVER_COUNTRIES', 'default' => 'Netherlands']),
        'domain',
        __('plugins.settings.the_cores'),
        __('plugins.settings.default'),
        __('plugins.settings.read_by', ['service' => 'caddy', 'variable' => 'DOMAIN']),
        'vpn.provider',
        __('plugins.settings.a_namespaces', ['namespace' => 'vpn-pack']),
        __('plugins.settings.secrets'),
        __('plugins.settings.secrets_at_terminal'),
        'vpn.key',
        __('plugins.settings.secret_held'),
        __(WhereASecretCameFrom::Operator->saidOnTheScreen()),
        'vpn.user',
        __('plugins.settings.secret_unset'),
        __(WhereASecretCameFrom::Unsaid->saidOnTheScreen()),
    )
        ->and(theTwoLinesUnder($drawn->said(), 'vpn.key'))->toContain(__('plugins.settings.secret_held'))
        ->and(theTwoLinesUnder($drawn->said(), 'vpn.user'))->toContain(__('plugins.settings.secret_unset'))
        ->and($drawn->said())->not->toContain('tls.email', 'ops@example.org', 'proxy.token', __('plugins.settings.a_namespaces', ['namespace' => 'gluetun']))
        ->and($drawn->offers())->toContain(__('plugins.settings.change'), __('plugins.settings.back_to_default'));
});

it('says so where the plugin is no longer installed, and offers the way back to the plugins', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(theGuardsSettingsScreen(aStackThatSetsTheGuard(), plugin: 'tdarr'));

    expect($drawn->said())->toContain(__('plugins.settings.not_installed'))
        ->and($drawn->offers())->toContain(__('plugins.settings.back'))
        ->and($drawn->said())->not->toContain('vpn.countries');
});

it('says where a plugin\'s services read no settings and no secrets', function (): void {
    $tuning = AStackThatTunesItsSettings::answering(TheServicesSettings::of(TheSettingsRead::these(), TheSecretsRead::these()), WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended());
    $drawn = WhatTheDeviceWouldDraw::by(theGuardsSettingsScreen($tuning));

    expect($drawn->said())->toContain(__('plugins.settings.none'), __('plugins.settings.no_secrets'));
});

it('opens one of the plugin\'s settings seeded with its value, and opens nothing for a secret or another service\'s setting', function (): void {
    $screen = theGuardsSettingsScreen(aStackThatSetsTheGuard());
    $screen->answer();
    $screen->change('vpn.key');
    $screen->change('tls.email');

    expect($screen->changing)->toBeNull();

    $screen->change('vpn.countries');
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->changing?->named)->toBe('vpn.countries')
        ->and($screen->typed)->toBe('Belgium')
        ->and($drawn->offers())->toContain(__('plugins.settings.rehearse'), __('plugins.settings.never'));

    $screen->never();

    expect($screen->changing)->toBeNull()
        ->and($screen->typed)->toBe('');
});

it('rehearses what is typed, follows the work, and draws it labelled as a rehearsal with every service it restarts', function (): void {
    $tuning = aStackThatSetsTheGuard();
    $screen = theGuardsSettingsScreen($tuning);
    $screen->answer();
    $screen->change('vpn.countries');
    $screen->typed = 'Germany';
    $screen->rehearse();

    expect($screen->awaitsAnOutcome())->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('plugins.settings.working'))
        ->and(array_map(static fn(ASettingChange $change): string => sprintf('%s %s=%s', $change->how()->asked(), $change->setting(), $change->value()->said()), $tuning->rehearsed()))->toBe(['settings-set vpn.countries=Germany']);

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->awaitsAnOutcome())->toBeFalse()
        ->and(array_map(static fn(Job $job): string => $job->shown(), $tuning->followed()))->toBe([AStackThatTunesItsSettings::THE_REHEARSAL])
        ->and($drawn->said())->toContain(
            __(WhatTheSettingRunDid::Rehearsed->saidOnTheScreen()),
            __('plugins.settings.to', ['value' => 'Germany']),
            __('plugins.settings.was', ['was' => 'Belgium']),
            __('plugins.settings.restarts', ['services' => 'gluetun, qbittorrent']),
            __('plugins.settings.regenerates', ['services' => 'gluetun']),
        )
        ->and($drawn->offers())->toContain(__('plugins.settings.agree'))
        ->and($tuning->carriedOut())->toBe([]);
});

it('writes the change the rehearsal described, carrying back its offer, and draws what the stack then lists', function (): void {
    $tuning = aStackThatSetsTheGuard();
    $screen = aRehearsalOfGermany($tuning);
    $screen->typed = 'France';
    $screen->agree();

    expect(array_map(static fn(ASettingChangeAgreed $agreed): string => sprintf(
        '%s=%s %s',
        $agreed->change()->setting(),
        $agreed->change()->value()->said(),
        $agreed->offer()->either(named: static fn(string $named): TheWordCarriedOut => new TheWordCarriedOut($named), none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'))->said,
    ), $tuning->carriedOut()))->toBe(['vpn.countries=Germany settings-set:vpn.countries:7']);

    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(__(WhatTheSettingRunDid::Applied->saidOnTheScreen()), 'Germany')
        ->and($drawn->offers())->not->toContain(__('plugins.settings.agree'))
        ->and($tuning->askings())->toBe(1);
});

it('sends no yes where nothing was rehearsed, or where the last answer was not a rehearsal of that change', function (): void {
    $tuning = aStackThatSetsTheGuard();
    $screen = theGuardsSettingsScreen($tuning);
    $screen->answer();
    $screen->agree();
    $screen->rehearse();

    $notARehearsal = aRehearsalOfGermany(aStackThatSetsTheGuard(rehearsal: WhatBecameOfTheWork::answered(aChangeToTheCountries(WhatTheSettingRunDid::NotWritten))));
    $notARehearsal->agree();

    expect($tuning->carriedOut())->toBe([])
        ->and($tuning->rehearsed())->toBe([])
        ->and($notARehearsal->howItIsGoing()->change?->mayBeAgreed)->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($notARehearsal)->said())->toContain(__(WhatTheSettingRunDid::NotWritten->saidOnTheScreen()));
});

it('sends no yes against a rehearsal no longer on the screen: the setting closed or opened afresh, or rehearsed again and not yet back', function (string $then): void {
    $tuning = aStackThatSetsTheGuard();
    $screen = aRehearsalOfGermany($tuning);

    match ($then) {
        'closed' => $screen->never(),
        'opened afresh' => $screen->change('vpn.countries'),
        default => $screen->rehearse(),
    };
    $screen->agree();

    expect($tuning->carriedOut())->toBe([]);
})->with(['closed' => ['closed'], 'opened afresh' => ['opened afresh'], 'rehearsed again' => ['rehearsed again']]);

it('rehearses putting a setting back to its default, and says where that restarts nothing', function (): void {
    $back = ASettingChange::backToDefault('vpn.countries');
    $tuning = aStackThatSetsTheGuard(rehearsal: WhatBecameOfTheWork::answered(aChangeToTheCountries(WhatTheSettingRunDid::Rehearsed, $back, restarts: Services::none())));
    $screen = theGuardsSettingsScreen($tuning);
    $screen->answer();
    $screen->backToDefault('vpn.key');
    $screen->backToDefault('vpn.countries');
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect(array_map(static fn(ASettingChange $change): string => $change->how()->asked(), $tuning->rehearsed()))->toBe(['settings-clear'])
        ->and($drawn->said())->toContain(__('plugins.settings.to_default'), __('plugins.settings.restarts_nothing'))
        ->and($drawn->offers())->toContain(__('plugins.settings.agree_back'))
        ->and($drawn->offers())->not->toContain(__('plugins.settings.rehearse'));

    $screen->rehearse();
    $screen->agree();

    expect(array_map(static fn(ASettingChangeAgreed $agreed): string => $agreed->change()->how()->asked(), $tuning->carriedOut()))->toBe(['settings-clear'])
        ->and($tuning->rehearsed())->toHaveCount(1);
});

it('draws what a change costs and why nothing restarted, in the stack\'s words', function (): void {
    $written = aChangeToTheCountries(WhatTheSettingRunDid::Applied, consequence: 'The tunnel drops for a moment', unrestarted: 'gluetun was stopped');
    $screen = aRehearsalOfGermany(aStackThatSetsTheGuard(change: WhatBecameOfTheWork::answered($written)));
    $screen->agree();
    $screen->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(
        __('plugins.settings.consequence', ['consequence' => 'The tunnel drops for a moment']),
        __('plugins.settings.unrestarted', ['why' => 'gluetun was stopped']),
    );
});

it('draws a refusal in the stack\'s own words, reads the settings again, and offers starting again', function (): void {
    $why = ARefusalInItsWords::said('vpn.countries changed since it was read', '', WhatTheRefusalNamed::nothing(), 'Read the settings again and change it from there');
    $tuning = aStackThatSetsTheGuard(change: WhatBecameOfTheWork::refused($why));
    $screen = aRehearsalOfGermany($tuning);
    $screen->agree();
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(
        __('plugins.settings.refused'),
        'vpn.countries changed since it was read',
        __('stacks.refusal.remedy', ['remedy' => 'Read the settings again and change it from there']),
    )
        ->and($drawn->offers())->toContain(__('plugins.settings.start_again'))
        ->and($tuning->askings())->toBe(2);

    $screen->startAgain();

    expect($screen->howItIsGoing()->refused)->toBeNull()
        ->and($screen->changing?->named)->toBe('vpn.countries');
});

it('says so where the stack has no outcome for the work any more', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(aRehearsalOfGermany(aStackThatSetsTheGuard(rehearsal: WhatBecameOfTheWork::ended())));

    expect($drawn->said())->toContain(__('plugins.settings.no_outcome'))
        ->and($drawn->offers())->toContain(__('plugins.settings.start_again'));
});

it('draws the settings the stream says, without reading them again', function (): void {
    $tuning = aStackThatSetsTheGuard();
    $screen = theGuardsSettingsScreen($tuning, hearing: AStackThatSpeaksUp::holdingOpen(WhatWasHeard::aSignOfLife()->settledAs(theMachinesSettings('Sweden'))));

    WhatTheDeviceWouldDraw::onTheSecondFrame($screen);
    $screen->listen();
    $drawn = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($drawn)->toContain('Sweden')
        ->and($drawn)->not->toContain('Belgium')
        ->and($tuning->askings())->toBe(1);
});

it('reads the plugin and its settings again when asked', function (): void {
    $tuning = aStackThatSetsTheGuard();
    $extending = AStackThatExtendsItself::listing(theGuardInstalled());
    $screen = theGuardsSettingsScreen($tuning, $extending);
    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($tuning->askings())->toBe(2)
        ->and($extending->asked())->toBe(['list', 'list']);
});

it('draws what stood in the way of reading the plugins or the settings, and a session that ended', function (): void {
    $plugins = theGuardsSettingsScreen(aStackThatSetsTheGuard(), AStackThatExtendsItself::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));
    $settings = theGuardsSettingsScreen(AStackThatTunesItsSettings::sayingAtOnce(WhatWasFoundOfTheSettings::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)), WhatBecameOfTheWork::ended()));
    $signedOut = theGuardsSettingsScreen(aStackThatSetsTheGuard(), signedIn: false);
    $didNotAnswer = __(KindOfObstacle::StackDidNotAnswer->said());

    expect(WhatTheDeviceWouldDraw::by($plugins)->said())->toContain($didNotAnswer)
        ->and(WhatTheDeviceWouldDraw::by($settings)->said())->toContain($didNotAnswer)
        ->and(WhatTheDeviceWouldDraw::by($signedOut)->said())->toContain(__('connection.session_has_ended'));
});

it('draws what stood in the way of a change, and a session that ended while it was followed', function (): void {
    $met = aRehearsalOfGermany(AStackThatTunesItsSettings::sayingAtOnce(WhatWasFoundOfTheSettings::found(theMachinesSettings()), WhatBecameOfTheWork::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))));
    $keychain = AKeychainInMemory::working();
    $ended = theGuardsSettingsScreen(aStackThatSetsTheGuard(), keychain: $keychain);
    $ended->answer();
    $ended->change('vpn.countries');
    $ended->rehearse();
    $keychain->forget(theStackWhosePluginIsSet()->id());
    $ended->whileItRuns();

    expect(WhatTheDeviceWouldDraw::by($met)->said())->toContain(__(KindOfObstacle::StackDidNotAnswer->said()))
        ->and($ended->howItIsGoing()->stands->went->cameBack())->toBeFalse()
        ->and(WhatTheDeviceWouldDraw::by($ended)->said())->toContain(__('connection.session_has_ended'));
});

/** The port the application binds once the stand-in stands in for every machine. */
function theStandInsTuning(): SettingWhatServicesRead
{
    config(['dx.stands_in' => true]);
    app()->register(new DxServiceProvider(app()), force: true);

    return app()->make(SettingWhatServicesRead::class);
}

/**
 * Every machine the stand-in holds, the one that answers first.
 *
 * @return list<Stack>
 */
function theStandInsMachines(): array
{
    $stacks = [];

    foreach (app()->make(Stacks::class)->configured() as $held) {
        $stacks[] = $held;
    }

    return $stacks;
}

it('takes a change on against a stand-in machine and follows it to what the machine lists', function (): void {
    $tuning = theStandInsTuning();
    $stacks = theStandInsMachines();
    $session = Session::of('a-session-not-a-secret');
    $said = [];

    foreach ([ASettingChange::toHold('vpn.countries', 'Germany'), ASettingChange::backToDefault('vpn.countries')] as $change) {
        $job = $tuning->rehearse($stacks[0], $session, $change)->either(
            underway: static fn(Job $job): Job => $job,
            answered: static fn(): Job => Job::named('answered at once'),
            ended: static fn(): Job => Job::named('ended'),
            refused: static fn(): Job => Job::named('refused'),
            met: static fn(): Job => Job::named('met'),
        );
        $said[] = sprintf('%s %s', $job->shown(), $tuning->whatBecameOf($stacks[0], $session, $job)->either(
            underway: static fn(): TheWordCarriedOut => new TheWordCarriedOut('underway'),
            answered: static fn(): TheWordCarriedOut => new TheWordCarriedOut('answered'),
            ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
            refused: static fn(): TheWordCarriedOut => new TheWordCarriedOut('refused'),
            met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
        )->said);
    }

    expect($said)->toBe(['settings-set answered', 'settings-clear answered']);
});

/** What a grant's line saying when it was approved begins with. */
function aGrantLineOnTheSettingsPage(): string
{
    $said = __('stacks.credentials.grants.approved', ['when' => '']);

    return is_string($said) ? $said : '';
}

it('lists each password of another service granted to the plugin, when it was approved, and where it is ended', function (): void {
    $grant = ACredentialGrant::of(APluginId::called('gluetun'), 'plex.token', ServiceId::called('plex'), 'library-scan', AMomentAsWritten::inSeconds((string) WHEN_THE_GUARD_WAS_GRANTED));
    $granted = ThePlugins::listed(TheInstalledPlugins::these(theGuardPlugin()->holding(TheCredentialGrants::of($grant))), ThePluginSources::these(), TheAnswersOutOfContract::these());
    $drawn = WhatTheDeviceWouldDraw::by(theGuardsSettingsScreen(aStackThatSetsTheGuard(), AStackThatExtendsItself::listing($granted)));
    $none = WhatTheDeviceWouldDraw::by(theGuardsSettingsScreen(aStackThatSetsTheGuard()));

    expect($drawn->said())->toContain(
        __('plugins.settings.grants'),
        __('stacks.credentials.grants.of', ['credential' => 'plex.token', 'whose' => 'plex']),
        __('stacks.credentials.grants.granted', ['plugin' => 'gluetun', 'fills' => 'library-scan']),
        __('plugins.settings.grants_ended_with_the_passwords'),
    )->and($drawn->offers())->toContain(__('plugins.settings.to_the_passwords'))
        ->and($drawn->offers())->not->toContain(__('stacks.credentials.grants.revoke'))
        ->and(array_filter($drawn->said(), static fn(string $line): bool => str_starts_with($line, aGrantLineOnTheSettingsPage())))->toHaveCount(1)
        ->and($none->said())->toContain(__('plugins.settings.no_grants'))
        ->and($none->offers())->not->toContain(__('plugins.settings.to_the_passwords'));
});

/**
 * The two lines drawn under the first line saying this, which is where a card says whose it is and how it stands.
 *
 * @param list<string> $said
 *
 * @return list<string>
 */
function theTwoLinesUnder(array $said, string $line): array
{
    $at = array_keys($said, $line, strict: true)[0] ?? count($said);

    return array_slice($said, $at + 1, 2);
}

it('opens with nothing typed into a setting\'s field', function (): void {
    expect(theGuardsSettingsScreen(aStackThatSetsTheGuard())->typed)->toBe('');
});
