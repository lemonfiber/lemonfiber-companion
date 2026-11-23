<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\OutOfTheOperatorsScreens;
use Illuminate\Contracts\Translation\Translator;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Kernel\Api\ACredentialHeld;
use Modules\Kernel\Api\ADecision;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheCredentialGrants;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheServicesWaiting;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\Unattended;
use Modules\Kernel\Api\WhatAChangeAsksFor;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhatKeepsItRunning;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Kernel\Api\WhatSetupDecided as TheDecisions;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Kernel\Api\WhatTheStoreProtects;
use Modules\Kernel\Api\WhatUsesIt;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\WhoMadeACredential;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\HowADecisionIsChanged;
use Modules\Operator\Internal\Screens\WhatSetupDecided;
use Modules\Operator\Internal\ViewModels\ADecisionAsShown;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeRouter;
use Tests\Support\AroundThePhone;
use Tests\Support\ASettingChangeAsReported;
use Tests\Support\Fakes\AJobThatSays;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatHoldsCredentials;
use Tests\Support\Fakes\AStackThatHosts;
use Tests\Support\Fakes\AStackThatSaysWhatSetupDecided;
use Tests\Support\Fakes\AStackThatTunesItsSettings;
use Tests\Support\Fakes\AStackToldToChangeSomething;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

/** What setup decided on the loft: some held, some never written, two kept in the settings store and two credentials. */
function whatTheLoftDecided(): TheDecisions
{
    return TheDecisions::beside(
        Settings::of(
            Setting::called('DATA_ROOT', WhatASettingHolds::shown('/srv/media'), WhoPutItThere::bundled()),
            Setting::called('INDEXER_APIKEY', WhatASettingHolds::withheld('set, and not shown'), WhoPutItThere::bundled()),
            Setting::called('BIND_TOKEN', WhatASettingHolds::withheld('set, and not shown'), WhoPutItThere::bundled()),
        ),
        ADecision::inTheConfiguration('DATA_ROOT', Cost::Consequential, 'where every download lives', held: true, credential: false),
        ADecision::inTheConfiguration('LEMONFIBER_USENET', Cost::Consequential, 'whether Usenet runs at all', held: false, credential: false),
        ADecision::inTheConfiguration('USENET_HOST', Cost::Cheap, 'which provider articles come from', held: false, credential: false),
        ADecision::inTheConfiguration('USENET_PASS', Cost::Cheap, 'the password the account is reached with', held: false, credential: true),
        ADecision::inTheConfiguration('INDEXER_APIKEY', Cost::Cheap, 'the key searches are sent with', held: true, credential: true),
        ADecision::inTheSettings('VPN_PROVIDER', Cost::Consequential, 'which provider carries the torrents', held: true, credential: false, setting: 'gluetun:provider'),
        ADecision::inTheSettings('LEMONFIBER_LOCALE', Cost::Cheap, 'the language the house says it has', held: false, credential: false, setting: 'LEMONFIBER_LOCALE'),
        ADecision::inTheSettings('TZ', Cost::Cheap, 'the zone every clock keeps', held: false, credential: false, setting: 'house:zone'),
    );
}

/** The loft's settings store, holding the provider for the tunnel and one secret. */
function theLoftsStore(): WhatWasFoundOfTheSettings
{
    return WhatWasFoundOfTheSettings::found(theLoftsSettings());
}

/** Every setting and secret of the loft's settings store. */
function theLoftsSettings(): TheServicesSettings
{
    return TheServicesSettings::of(
        TheSettingsRead::these(
            ASetting::read('gluetun:provider', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('gluetun'), 'VPN_SERVICE_PROVIDER', ASettingValue::nothing())), ASettingValue::value('mullvad')),
            ASetting::read('LEMONFIBER_LOCALE', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing()),
        ),
        TheSecretsRead::these(ASecret::read('vpn.key', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(), held: true, from: WhereASecretCameFrom::Operator)),
    );
}

/** What giving the settings store's `$named` this value came to, as the stack reported it. */
function aChangeKeptAs(ASettingChange $change, WhatTheSettingRunDid $did): TheSettingAsChanged
{
    return TheSettingAsChanged::said(
        ASettingChangeAsReported::moves($change, ASettingValue::nothing()),
        WhatTheChangeReaches::said(Services::these(ServiceId::called('gluetun')), Services::these(ServiceId::called('gluetun')), Services::none(), TheServicesWaiting::none(), ''),
        $did,
        AnOffer::named('settings-set:3'),
        theLoftsSettings(),
    );
}

/** The loft's settings store, rehearsing giving `$named` this value and, on a yes, writing it. */
function aStoreThatKeeps(string $named, string $value): AStackThatTunesItsSettings
{
    $change = ASettingChange::toHold($named, $value);

    return AStackThatTunesItsSettings::answering(
        theLoftsSettings(),
        WhatBecameOfTheWork::answered(aChangeKeptAs($change, WhatTheSettingRunDid::Rehearsed)),
        WhatBecameOfTheWork::answered(aChangeKeptAs($change, WhatTheSettingRunDid::Applied)),
    );
}

/**
 * Each change the settings store was asked to rehearse, then each it was asked to carry out, as words.
 *
 * @return array{list<string>, list<string>}
 */
function whatTheStoreWasAsked(AStackThatTunesItsSettings $tuning): array
{
    return [
        array_map(ASettingChangeAsReported::said(...), $tuning->rehearsed()),
        array_map(static fn(ASettingChangeAgreed $agreed): string => ASettingChangeAsReported::said($agreed->change()), $tuning->carriedOut()),
    ];
}

/** The loft's credentials: the indexer key refused, the provider password absent, one the decisions do not name, and any more given. */
function theLoftsCredentials(WhereACredentialStands $password = WhereACredentialStands::Absent, ACredentialHeld ...$also): AStackThatHoldsCredentials
{
    return AStackThatHoldsCredentials::holding(TheCredentialsHeld::of(
        WhatTheStoreProtects::said('Plain files readable only by you', Remarks::of(), Remarks::of()),
        TheCredentialGrants::of(),
        ACredentialHeld::described('Indexer API key', 'INDEXER_APIKEY', WhereACredentialStands::Invalid, WhoMadeACredential::Operator, WhatUsesIt::of('the indexer aggregator'), ''),
        ACredentialHeld::described('Usenet password', 'USENET_PASS', $password, WhoMadeACredential::Operator, WhatUsesIt::of('the download client'), ''),
        ACredentialHeld::described('Media server decline key', 'decline/jellyfin.key', WhereACredentialStands::Active, WhoMadeACredential::Lemonfiber, WhatUsesIt::of('the decline service'), ''),
        ...$also,
    ));
}

/** Turning Usenet on, which opens an account to obtain and then three settings, one of them no decision names. */
function turningUsenetOn(): WhereTheChangeStands
{
    return WhereTheChangeStands::at(
        ProposedChange::of('LEMONFIBER_USENET', 'on', WhatItHoldsNow::nothingYet(), Cost::Consequential),
        Stance::Applied,
        WhatAChangeComesTo::nothing()
            ->asking(WhatAChangeAsksFor::toObtain('A Usenet provider account', 'articles are fetched from it'))
            ->asking(WhatAChangeAsksFor::keptIn('USENET_HOST', 'Usenet is asked for once this is on', 'USENET_HOST'))
            ->asking(WhatAChangeAsksFor::keptIn('USENET_PASS', 'Usenet is asked for once this is on', 'USENET_PASS'))
            ->asking(WhatAChangeAsksFor::keptIn('USENET_CONNECTIONS', 'Usenet is asked for once this is on', 'USENET_CONNECTIONS')),
    );
}

function theDecidedScreen(
    ?AStackThatSaysWhatSetupDecided $decided = null,
    ?AStackToldToChangeSomething $adjusting = null,
    ?AStackThatHoldsCredentials $credentials = null,
    ?AShareSheetThatWasOffered $sheet = null,
    ?AStackThatHosts $hosting = null,
    ?AStackThatTunesItsSettings $tuning = null,
): WhatSetupDecided {
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatSetupDecided(
        $decided ?? AStackThatSaysWhatSetupDecided::with(whatTheLoftDecided()),
        $adjusting ?? AStackToldToChangeSomething::saying(WhereTheChangeStands::at(ProposedChange::of('DATA_ROOT', '/srv/elsewhere', WhatItHoldsNow::shown('/srv/media'), Cost::Consequential), Stance::Pending, WhatAChangeComesTo::nothing())),
        $tuning ?? AStackThatTunesItsSettings::sayingAtOnce(theLoftsStore(), WhatBecameOfTheWork::noOutcome()),
        $credentials ?? theLoftsCredentials(),
        $hosting ?? AStackThatHosts::with(WhatRunsUnattended::keptBy(WhatKeepsItRunning::Launchd, Unattended::called('boot', 'lemonfiber up --at-boot', 'brings the stack back', HowItIsHosted::Stopped))),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        $sheet ?? AShareSheetThatWasOffered::working(),
        app(Translator::class),
        AJobThatSays::nothing(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);
    WhatTheDeviceWouldDraw::onTheFourthFrame($screen);

    return $screen;
}

/** The row the screen shows for one key. */
function theDecisionOn(WhatSetupDecided $screen, string $key): ADecisionAsShown
{
    foreach ($screen->answer()->decisions as $decision) {
        if ($decision->key === $key) {
            return $decision;
        }
    }

    throw new LogicException(sprintf('no row for %s', $key));
}

it('lists every decision the stack lists, held or not, with what it holds or that it is not set, its cost and what changing it affects', function (): void {
    $screen = theDecidedScreen();

    expect([$screen->answer()->waits, $screen->answer()->waits])->toBe([false, false]);
    $said = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();

    expect(array_map(static fn(ADecisionAsShown $row): string => $row->key, $screen->answer()->decisions))
        ->toBe(['DATA_ROOT', 'LEMONFIBER_USENET', 'USENET_HOST', 'USENET_PASS', 'INDEXER_APIKEY', 'VPN_PROVIDER', 'LEMONFIBER_LOCALE', 'TZ'])
        ->and($said)->toContain(
            'DATA_ROOT',
            '/srv/media',
            'where every download lives',
            __('decided.cost.consequential'),
            'LEMONFIBER_USENET',
            __('decided.not_set'),
            'whether Usenet runs at all',
            'USENET_HOST',
            __('decided.cost.cheap'),
            'which provider articles come from',
            'VPN_PROVIDER',
            'mullvad',
        )
        ->and(theDecisionOn($screen, 'DATA_ROOT'))->toEqual(new ADecisionAsShown('DATA_ROOT', 'where every download lives', '/srv/media', isSet: true, costSaid: 'decided.cost.consequential', how: HowADecisionIsChanged::Here, goes: '', command: '', standsSaid: '', setting: null))
        ->and(theDecisionOn($screen, 'USENET_HOST'))->toEqual(new ADecisionAsShown('USENET_HOST', 'which provider articles come from', '', isSet: false, costSaid: 'decided.cost.cheap', how: HowADecisionIsChanged::Here, goes: '', command: '', standsSaid: '', setting: null));
});

it('changes a decision the configuration keeps through the stack review, by config-set, held or not', function (string $key, string $seeded): void {
    $adjusting = AStackToldToChangeSomething::saying(WhereTheChangeStands::at(ProposedChange::of($key, 'news.example', WhatItHoldsNow::nothingYet(), Cost::Cheap), Stance::Pending, WhatAChangeComesTo::nothing()));
    $screen = theDecidedScreen(adjusting: $adjusting);

    $screen->change($key);

    expect($screen->changing())->toBe($key)
        ->and($screen->typed)->toBe($seeded);

    $screen->typed = 'news.example';
    $screen->wouldBe($key);

    expect($adjusting->rehearsals())->toBe(1)
        ->and($adjusting->writes())->toBe(0)
        ->and($adjusting->askedFor()?->key)->toBe($key)
        ->and($adjusting->askedFor()?->value)->toBe('news.example')
        ->and(WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->offers())->toContain(__('config.agree'));

    $screen->agree($key);

    expect($adjusting->writes())->toBe(1);
})->with([
    'held' => ['DATA_ROOT', '/srv/media'],
    'never written' => ['USENET_HOST', ''],
]);

it('changes a decision the settings store keeps here through the stack review, by settings-set under the name the stack gives, held or not', function (string $key, string $named, string $seeded): void {
    $decided = AStackThatSaysWhatSetupDecided::with(whatTheLoftDecided());
    $adjusting = AStackToldToChangeSomething::saying(turningUsenetOn());
    $tuning = aStoreThatKeeps($named, 'nl');
    $screen = theDecidedScreen(decided: $decided, adjusting: $adjusting, tuning: $tuning);

    $screen->change($key);

    expect($screen->changing())->toBe($key)
        ->and($screen->typed)->toBe($seeded)
        ->and(WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->offers())->toContain(__('config.what_would_happen'));

    $askings = $decided->askings();
    $screen->typed = 'nl';
    $screen->wouldBe($key);

    expect($screen->awaitsAnOutcome())->toBeTrue();

    $screen->whileItRuns();
    $screen->whileItRuns();
    $screen->whileItRuns();
    $screen->answer();

    expect(whatTheStoreWasAsked($tuning))->toBe([[sprintf('settings-set %s=nl', $named)], []])
        ->and($screen->awaitsAnOutcome())->toBeFalse()
        ->and($screen->howItIsGoing()->change?->mayBeAgreed)->toBeTrue()
        ->and($decided->askings())->toBe($askings)
        ->and(WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->offers())->toContain(__('config.agree'));

    $askings = $decided->askings();
    $screen->agree($key);
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->answer();

    expect(whatTheStoreWasAsked($tuning))->toBe([[sprintf('settings-set %s=nl', $named)], [sprintf('settings-set %s=nl', $named)]])
        ->and($decided->askings())->toBe($askings + 1)
        ->and($adjusting->rehearsals())->toBe(0)
        ->and($adjusting->writes())->toBe(0);
})->with([
    'held, by a plugin' => ['VPN_PROVIDER', 'gluetun:provider', 'mullvad'],
    'never written, by the core' => ['LEMONFIBER_LOCALE', 'LEMONFIBER_LOCALE', ''],
    'not listed by the settings read' => ['TZ', 'house:zone', ''],
]);

it('links a decision the settings store keeps to the screen that keeps it as well, and nowhere where that screen does not list it', function (): void {
    $screen = theDecidedScreen();
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();

    expect(array_map(static fn(string $key): ADecisionAsShown => theDecisionOn($screen, $key), ['VPN_PROVIDER', 'LEMONFIBER_LOCALE', 'TZ']))->toEqual([
        new ADecisionAsShown('VPN_PROVIDER', 'which provider carries the torrents', 'mullvad', isSet: true, costSaid: 'decided.cost.consequential', how: HowADecisionIsChanged::Here, goes: AStacksScreen::PluginSettings->forTheStacks($stack->id(), APluginId::called('gluetun')), command: '', standsSaid: '', setting: 'gluetun:provider'),
        new ADecisionAsShown('LEMONFIBER_LOCALE', 'the language the house says it has', '', isSet: false, costSaid: 'decided.cost.cheap', how: HowADecisionIsChanged::Here, goes: AStacksScreen::OwnSettings->forTheStack($stack->id()), command: '', standsSaid: '', setting: 'LEMONFIBER_LOCALE'),
        new ADecisionAsShown('TZ', 'the zone every clock keeps', '', isSet: false, costSaid: 'decided.cost.cheap', how: HowADecisionIsChanged::Here, goes: '', command: '', standsSaid: '', setting: 'house:zone'),
    ])
        ->and(theDecisionOn($screen, 'DATA_ROOT')->setting)->toBeNull();
});

it('says a decision the settings store keeps and does not list is set where the stack holds it, without its value', function (): void {
    $screen = theDecidedScreen(decided: AStackThatSaysWhatSetupDecided::with(TheDecisions::beside(Settings::none(), ADecision::inTheSettings('TZ', Cost::Cheap, 'the zone every clock keeps', held: true, credential: false, setting: 'house:zone'))));

    $said = WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();

    expect([theDecisionOn($screen, 'TZ')->isSet, theDecisionOn($screen, 'TZ')->holds])->toBe([true, ''])
        ->and($said)->toContain(__('decided.not_listed_where_kept'));
    expect($said)->not->toContain(__('decided.not_set'));
});

it('leaves what a settings store change came to once the decision is left or another opened', function (string $then): void {
    $tuning = aStoreThatKeeps('LEMONFIBER_LOCALE', 'nl');
    $screen = theDecidedScreen(tuning: $tuning);
    $screen->change('LEMONFIBER_LOCALE');
    $screen->typed = 'nl';
    $screen->wouldBe('LEMONFIBER_LOCALE');
    $screen->whileItRuns();
    $screen->howItIsGoing();

    $then === 'never' ? $screen->never() : $screen->change('VPN_PROVIDER');

    expect($screen->howItIsGoing()->stands->wasAsked())->toBeFalse()
        ->and([$screen->asked, $screen->report, $screen->awaitsAnOutcome()])->toBe([null, null, false]);
})->with(['never', 'another']);

it('puts no settings store change under a name the stack lists as a credential, for another key than the open one, or a yes to a rehearsal of anything else', function (): void {
    $tuning = aStoreThatKeeps('LEMONFIBER_LOCALE', 'nl');
    $decided = AStackThatSaysWhatSetupDecided::with(TheDecisions::beside(
        Settings::none(),
        ADecision::inTheSettings('VPN_PROVIDER', Cost::Cheap, 'which provider carries the torrents', held: true, credential: false, setting: 'vpn.key'),
        ADecision::inTheSettings('USENET_PASS', Cost::Cheap, 'the password the account is reached with', held: false, credential: false, setting: 'usenet:password'),
        ADecision::inTheSettings('LEMONFIBER_LOCALE', Cost::Cheap, 'the language the house says it has', held: false, credential: false, setting: 'LEMONFIBER_LOCALE'),
        ADecision::inTheSettings('TZ', Cost::Cheap, 'the zone every clock keeps', held: false, credential: false, setting: 'house:zone'),
    ));
    $screen = theDecidedScreen(decided: $decided, credentials: theLoftsCredentials(), tuning: $tuning);

    foreach (['VPN_PROVIDER', 'USENET_PASS'] as $credential) {
        $screen->change($credential);

        expect($screen->changing())->toBe('');

        $screen->changing = $credential;
        $screen->typed = 'x';
        $screen->wouldBe($credential);
    }

    $screen->change('LEMONFIBER_LOCALE');
    $screen->agree('LEMONFIBER_LOCALE');
    $screen->typed = null;
    $screen->wouldBe('LEMONFIBER_LOCALE');
    $screen->typed = 'nl';
    $screen->wouldBe('LEMONFIBER_LOCALE');
    $screen->whileItRuns();
    $screen->howItIsGoing();
    $screen->wouldBe('TZ');
    $screen->agree('TZ');
    $forged = ASettingChange::toHold('vpn.key', 'x');
    $screen->asked = $forged;
    $screen->report = aChangeKeptAs($forged, WhatTheSettingRunDid::Rehearsed);
    $screen->agree('LEMONFIBER_LOCALE');

    expect(whatTheStoreWasAsked($tuning))->toBe([['settings-set LEMONFIBER_LOCALE=nl'], []]);

    $screen->asked = ASettingChange::toHold('LEMONFIBER_LOCALE', 'nl');
    $screen->report = aChangeKeptAs(ASettingChange::toHold('LEMONFIBER_LOCALE', 'nl'), WhatTheSettingRunDid::Rehearsed);
    $screen->agree('LEMONFIBER_LOCALE');

    expect(whatTheStoreWasAsked($tuning))->toBe([['settings-set LEMONFIBER_LOCALE=nl'], ['settings-set LEMONFIBER_LOCALE=nl']]);
});

it('opens nothing while a reading waits for a frame of its own, as the stack has not yet said what is a credential', function (): void {
    $screen = theDecidedScreen();
    $screen->again();
    $screen->change('DATA_ROOT');
    $screen->named = 'DATA_ROOT';
    $screen->another();

    expect($screen->answer()->waits)->toBeTrue()
        ->and($screen->changing())->toBe('');

    WhatTheDeviceWouldDraw::onTheFourthFrame($screen);
    $screen->change('DATA_ROOT');

    expect($screen->changing())->toBe('DATA_ROOT');
});

it('names a setting the configuration withholds as changed at the machine, though its decision does not say credential', function (): void {
    $screen = theDecidedScreen(decided: AStackThatSaysWhatSetupDecided::with(TheDecisions::beside(
        Settings::of(Setting::called('BIND_TOKEN', WhatASettingHolds::withheld('set, and not shown'), WhoPutItThere::bundled())),
        ADecision::inTheConfiguration('BIND_TOKEN', Cost::Cheap, 'the token the door is bound with', held: true, credential: false),
    )));
    $screen->change('BIND_TOKEN');

    expect([theDecisionOn($screen, 'BIND_TOKEN')->how, theDecisionOn($screen, 'BIND_TOKEN')->command])->toBe([HowADecisionIsChanged::AtTheMachine, 'lemonfiber config set BIND_TOKEN <value>'])
        ->and($screen->changing())->toBe('');
});

it('reads the decisions again when the settings store refuses a change, and not after a rehearsal', function (): void {
    $decided = AStackThatSaysWhatSetupDecided::with(whatTheLoftDecided());
    $tuning = AStackThatTunesItsSettings::sayingAtOnce(theLoftsStore(), WhatBecameOfTheWork::refused(ARefusalInItsWords::said('Not a provider gluetun knows.', 'Nothing has been written.', WhatTheRefusalNamed::nothing())));
    $screen = theDecidedScreen(decided: $decided, tuning: $tuning);
    $screen->answer();
    $screen->change('VPN_PROVIDER');
    $screen->typed = 'nobody';
    $screen->wouldBe('VPN_PROVIDER');
    $screen->answer();

    expect($decided->askings())->toBe(2)
        ->and($screen->howItIsGoing()->refused)->not->toBeNull()
        ->and(WhatTheDeviceWouldDraw::onTheFourthFrame($screen)->said())->toContain(__('plugins.settings.refused'));
});

it('shows each credential as set, not set or refused with the command at the machine, and never opens one', function (): void {
    $sheet = AShareSheetThatWasOffered::working();
    $adjusting = AStackToldToChangeSomething::saying(turningUsenetOn());
    $screen = theDecidedScreen(adjusting: $adjusting, sheet: $sheet);
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect(theDecisionOn($screen, 'INDEXER_APIKEY'))->toEqual(new ADecisionAsShown('INDEXER_APIKEY', 'the key searches are sent with', '', isSet: true, costSaid: 'decided.cost.cheap', how: HowADecisionIsChanged::AtTheMachine, goes: '', command: 'lemonfiber config set INDEXER_APIKEY <value>', standsSaid: 'decided.credential.refused', setting: null))
        ->and(theDecisionOn($screen, 'USENET_PASS')->standsSaid)->toBe('decided.credential.not_set')
        ->and($drawn->said())->toContain(__('decided.credential.refused'), __('decided.credential.not_set'), 'lemonfiber config set USENET_PASS <value>')
        ->and($drawn->offers())->not->toContain(__('config.change_key', ['key' => 'INDEXER_APIKEY']), __('config.change_key', ['key' => 'USENET_PASS']))
        ->and($drawn->offers())->toContain(__('decided.copy'));

    $screen->change('INDEXER_APIKEY');
    $screen->typed = 'a-key';
    $screen->wouldBe('INDEXER_APIKEY');
    $screen->agree('INDEXER_APIKEY');

    expect($screen->changing())->toBe('')
        ->and($adjusting->rehearsals())->toBe(0)
        ->and($adjusting->writes())->toBe(0);

    WhatTheDeviceWouldDraw::onTheFourthFrame($screen);
    $screen->changing = ' indexer_apikey ';
    $screen->typed = 'a-key';
    $screen->wouldBe(' indexer_apikey ');

    expect($adjusting->rehearsals())->toBe(0);

    expect($screen->answer()->waits)->toBeFalse();

    $screen->changing = 'INDEXER_APIKEY';
    $screen->typed = 'a-key';
    $screen->wouldBe('INDEXER_APIKEY');
    $screen->agree('INDEXER_APIKEY');
    $screen->agree('INDEXER_APIKEY', 'for_the_downloads');

    expect($adjusting->rehearsals())->toBe(0)
        ->and($adjusting->writes())->toBe(0)
        ->and($adjusting->landings())->toBe(0);

    WhatTheDeviceWouldDraw::onTheFourthFrame($screen);
    $screen->copy('lemonfiber config set INDEXER_APIKEY <value>');

    expect($sheet->copied()?->text())->toBe('lemonfiber config set INDEXER_APIKEY <value>')
        ->and($screen->copied)->toBe('decided.copied');
});

it('says where a credential stands as the credentials read says, and falls back to whether it is held', function (WhereACredentialStands $stands, string $said): void {
    expect(theDecisionOn(theDecidedScreen(credentials: theLoftsCredentials($stands)), 'USENET_PASS')->standsSaid)->toBe($said)
        ->and(theDecisionOn(theDecidedScreen(credentials: AStackThatHoldsCredentials::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))), 'INDEXER_APIKEY')->standsSaid)->toBe('decided.credential.set')
        ->and(theDecisionOn(theDecidedScreen(credentials: AStackThatHoldsCredentials::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))), 'USENET_PASS')->standsSaid)->toBe('decided.credential.not_set');
})->with([
    'absent' => [WhereACredentialStands::Absent, 'decided.credential.not_set'],
    'invalid' => [WhereACredentialStands::Invalid, 'decided.credential.refused'],
    'active' => [WhereACredentialStands::Active, 'decided.credential.proven'],
    'stale' => [WhereACredentialStands::Stale, 'decided.credential.set'],
    'rotating' => [WhereACredentialStands::Rotating, 'decided.credential.set'],
]);

it('shows whether the stack starts with the machine as the hosting read says, with the commands at the machine, and nothing to turn it', function (): void {
    $screen = theDecidedScreen();
    $drawn = WhatTheDeviceWouldDraw::onTheSecondFrame($screen);

    expect($screen->answer()->autostart?->standsSaid)->toBe(HowItIsHosted::Stopped->saidOnTheScreen())
        ->and($drawn->said())->toContain(__('decided.autostart.heading'), __(HowItIsHosted::Stopped->saidOnTheScreen()), 'lemonfiber hosting install boot', 'lemonfiber hosting remove boot')
        ->and(array_filter($drawn->offers(), static fn(mixed $said): bool => $said === __('decided.copy')))->toHaveCount(4)
        ->and(theDecidedScreen(hosting: AStackThatHosts::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)))->answer()->autostart?->standsSaid)->toBe('decided.autostart.unread')
        ->and(theDecidedScreen(hosting: AStackThatHosts::with(WhatRunsUnattended::keptBy(WhatKeepsItRunning::Launchd)))->answer()->autostart?->standsSaid)->toBe('decided.autostart.unread');
});

it('follows turning a way of downloading on through what the review opens, in its order, unproven until the login is proven', function (): void {
    $adjusting = AStackToldToChangeSomething::saying(turningUsenetOn());
    $credentials = theLoftsCredentials();
    $screen = theDecidedScreen(adjusting: $adjusting, credentials: $credentials);

    $screen->change('LEMONFIBER_USENET');
    $screen->typed = 'on';
    $screen->agree('LEMONFIBER_USENET');
    $drawn = WhatTheDeviceWouldDraw::onTheFourthFrame($screen);
    $opened = $screen->answer()->opened;

    expect($opened?->key)->toBe('LEMONFIBER_USENET')
        ->and($opened?->proven)->toBeFalse()
        ->and(array_map(static fn(ADecisionAsShown $step): array => [$step->key, $step->affects, $step->how], $opened->steps ?? []))->toBe([
            ['A Usenet provider account', 'articles are fetched from it', HowADecisionIsChanged::ToObtain],
            ['USENET_HOST', 'Usenet is asked for once this is on', HowADecisionIsChanged::Here],
            ['USENET_PASS', 'Usenet is asked for once this is on', HowADecisionIsChanged::AtTheMachine],
            ['USENET_CONNECTIONS', 'Usenet is asked for once this is on', HowADecisionIsChanged::Here],
        ])
        ->and([$opened->steps[0] ?? null, $opened->steps[3] ?? null])->toEqual([
            new ADecisionAsShown('A Usenet provider account', 'articles are fetched from it', '', isSet: false, costSaid: '', how: HowADecisionIsChanged::ToObtain, goes: '', command: '', standsSaid: '', setting: null),
            new ADecisionAsShown('USENET_CONNECTIONS', 'Usenet is asked for once this is on', '', isSet: false, costSaid: '', how: HowADecisionIsChanged::Here, goes: '', command: '', standsSaid: '', setting: null),
        ])
        ->and($drawn->said())->toContain(__('decided.opened.section', ['key' => 'LEMONFIBER_USENET']), __('decided.opened.unproven'), 'A Usenet provider account', __('decided.to_obtain'), 'lemonfiber config set USENET_PASS <value>');

    $screen->change('USENET_HOST');
    $screen->typed = 'news.example';
    $screen->wouldBe('USENET_HOST');

    expect($screen->changing())->toBe('USENET_HOST')
        ->and($adjusting->askedFor()?->key)->toBe('USENET_HOST')
        ->and($screen->answer()->opened?->key)->toBe('LEMONFIBER_USENET');

    $proven = theDecidedScreen(adjusting: AStackToldToChangeSomething::saying(turningUsenetOn()), credentials: theLoftsCredentials(WhereACredentialStands::Active));
    $proven->change('LEMONFIBER_USENET');
    $proven->typed = 'on';
    $proven->agree('LEMONFIBER_USENET');
    $drawnProven = WhatTheDeviceWouldDraw::onTheFourthFrame($proven);

    expect($proven->answer()->opened?->proven)->toBeTrue()
        ->and($drawnProven->said())->not->toContain(__('decided.opened.unproven'));
});

it('opens nothing to follow where a change only rehearsed or opened nothing', function (): void {
    $rehearsed = theDecidedScreen(adjusting: AStackToldToChangeSomething::saying(WhereTheChangeStands::at(ProposedChange::of('LEMONFIBER_USENET', 'on', WhatItHoldsNow::nothingYet(), Cost::Consequential), Stance::Pending, WhatAChangeComesTo::nothing()->asking(WhatAChangeAsksFor::toObtain('A Usenet provider account', 'articles are fetched from it')))));
    $rehearsed->change('LEMONFIBER_USENET');
    $rehearsed->typed = 'on';
    $rehearsed->agree('LEMONFIBER_USENET');

    $quiet = theDecidedScreen(adjusting: AStackToldToChangeSomething::saying(WhereTheChangeStands::at(ProposedChange::of('DATA_ROOT', '/srv/new', WhatItHoldsNow::shown('/srv/media'), Cost::Consequential), Stance::Applied, WhatAChangeComesTo::nothing())));
    $quiet->change('DATA_ROOT');
    $quiet->agree('DATA_ROOT');

    expect($rehearsed->answer()->opened)->toBeNull()
        ->and($quiet->answer()->opened)->toBeNull();
});

it('refuses a name the stack lists as a credential in the field for any other setting, and opens any other name through the same review', function (string $named): void {
    $adjusting = AStackToldToChangeSomething::saying(WhereTheChangeStands::at(ProposedChange::of('LEMONFIBER_EXPLANATIONS', 'off', WhatItHoldsNow::nothingYet(), Cost::Cheap), Stance::Pending, WhatAChangeComesTo::nothing()));
    $screen = theDecidedScreen(adjusting: $adjusting, credentials: theLoftsCredentials(WhereACredentialStands::Absent, ACredentialHeld::described('A spaced token', ' A_SPACED_TOKEN ', WhereACredentialStands::Active, WhoMadeACredential::Operator, WhatUsesIt::of('the door'), '')));
    $screen->turnTheFold();
    $screen->change('DATA_ROOT');

    expect([$screen->changing(), $screen->opensFromTheField()])->toBe(['DATA_ROOT', false]);

    $screen->named = $named;
    $screen->another();

    expect($screen->refusedName)->toBe(trim($named))
        ->and($screen->changing())->toBe('')
        ->and($screen->typed)->toBeNull()
        ->and(WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said())->toContain(__('decided.another.refused', ['key' => trim($named)]));

    $screen->named = ' LEMONFIBER_EXPLANATIONS ';
    $screen->another();
    $screen->typed = 'off';
    $screen->wouldBe('LEMONFIBER_EXPLANATIONS');

    expect($screen->refusedName)->toBeNull()
        ->and($screen->changing())->toBe('LEMONFIBER_EXPLANATIONS')
        ->and($screen->opensFromTheField())->toBeTrue()
        ->and($adjusting->askedFor()?->key)->toBe('LEMONFIBER_EXPLANATIONS')
        ->and(WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->offers())->toContain(__('config.agree'));
})->with([
    'a decision that is a credential, in lower case' => ['indexer_apikey'],
    'a credential decision the file does not hold' => [' USENET_PASS'],
    'a setting the stack withholds' => ['BIND_TOKEN'],
    'a secret of the settings store' => ['vpn.key'],
    'a credential the credentials read names' => ['decline/jellyfin.key'],
    'a credential the credentials read names with spaces around it' => ['a_spaced_token'],
]);

it('opens the field with nothing typed yet', function (): void {
    $screen = theDecidedScreen();
    $screen->named = 'LEMONFIBER_EXPLANATIONS';
    $screen->typed = 'left over';
    $screen->another();

    expect([$screen->changing(), $screen->typed])->toBe(['LEMONFIBER_EXPLANATIONS', '']);
});

it('refuses a credential the stack adds later from what it lists, with nothing kept in the app', function (): void {
    $screen = theDecidedScreen(decided: AStackThatSaysWhatSetupDecided::with(TheDecisions::beside(Settings::none(), ADecision::inTheConfiguration('A_NEW_TOKEN', Cost::Cheap, 'a token the stack added', held: false, credential: true))));
    $screen->named = 'A_NEW_TOKEN';
    $screen->another();

    expect($screen->refusedName)->toBe('A_NEW_TOKEN')
        ->and(theDecisionOn($screen, 'A_NEW_TOKEN')->how)->toBe(HowADecisionIsChanged::AtTheMachine);
});

it('opens nothing for an empty name, and hands on no words the screen does not name', function (): void {
    $sheet = AShareSheetThatWasOffered::working();
    $screen = theDecidedScreen(sheet: $sheet);

    expect($screen->opensFromTheField())->toBeFalse();

    $screen->another();

    expect([$screen->refusedName, $screen->changing(), $screen->typed])->toBe([null, '', null]);

    $screen->named = '  ';
    $screen->another();
    $screen->copy('rm -rf /');

    expect($screen->refusedName)->toBeNull()
        ->and($screen->changing())->toBe('')
        ->and($screen->typed)->toBeNull()
        ->and($sheet->copied())->toBeNull()
        ->and($screen->copied)->toBeNull();
});

it('folds the field for any other setting away until it is asked for', function (): void {
    $screen = theDecidedScreen();

    expect(WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said())->not->toContain(__('decided.another.explained'));

    $screen->turnTheFold();

    expect(WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said())->toContain(__('decided.another.explained'));

    $screen->turnTheFold();

    expect($screen->unfolded)->toBeFalse();
});

it('says what stopped the reading where the stack refused it', function (): void {
    $screen = theDecidedScreen(decided: AStackThatSaysWhatSetupDecided::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)));

    expect($screen->answer()->went->cameBack())->toBeFalse()
        ->and($screen->answer()->decisions)->toBe([]);
});

it('asks again after a change is agreed to and when asked again', function (): void {
    $decided = AStackThatSaysWhatSetupDecided::with(whatTheLoftDecided());
    $screen = theDecidedScreen(decided: $decided);
    $screen->answer();
    $screen->change('DATA_ROOT');
    $screen->agree('DATA_ROOT');
    $screen->answer();
    $screen->again();
    $screen->answer();

    expect($decided->askings())->toBe(3);
});

it('builds the member home in place of this screen for a member session', function (): void {
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::member('robin'));
    $built = '';
    $guard = new OutOfTheOperatorsScreens($keychain, ['stack' => $stack->id()->stored()], static function (string $class) use (&$built): string {
        $built = $class;

        return $class;
    });

    $guard->screen(WhatSetupDecided::class);

    $route = NativeRouter::registeredRoutes()[AStacksScreen::Decided->value] ?? [];

    expect(is_array($route) ? $route['class'] ?? null : null)->toBe(WhatSetupDecided::class)
        ->and($built)->toBe(WhatYouCanWatch::class);
});
