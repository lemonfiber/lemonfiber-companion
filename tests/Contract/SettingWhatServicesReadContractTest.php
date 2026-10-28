<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\Fingerprint;
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
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Tuners;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatTunesItsSettings;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The SettingWhatServicesRead contract, run against the adapter and against the fake.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose settings are read and changed. */
function aStackWhoseSettingsAreRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** The session the settings are read with. */
function theSessionTheSettingsAreReadWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * What a stack lists of its settings: one of a plugin's with a value of the operator's, one of the core's left as it came, and one secret of each kind.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackSaysOfItsSettings(array $changed = []): array
{
    return ['api_version' => 1, 'kind' => 'settings', 'data' => [
        'rehearsed' => false,
        'settings' => [
            ['name' => 'vpn.countries', 'whose' => ['of' => 'namespace', 'namespace' => 'gluetun'], 'readers' => [['service' => 'gluetun', 'variable' => 'SERVER_COUNTRIES', 'default' => 'Netherlands']], 'value' => 'Belgium', 'environment' => 'GLUETUN_SERVER_COUNTRIES'],
            ['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => [['service' => 'caddy', 'variable' => 'DOMAIN'], ['service' => 'gluetun', 'variable' => 'PUBLIC_HOST']]],
        ],
        'secrets' => [
            ['name' => 'vpn.key', 'whose' => ['of' => 'namespace', 'namespace' => 'gluetun'], 'readers' => [['service' => 'gluetun', 'variable' => 'WIREGUARD_PRIVATE_KEY']], 'held' => true, 'from' => 'operator'],
            ['name' => 'proxy.token', 'whose' => ['of' => 'core'], 'readers' => [['service' => 'caddy', 'variable' => 'TOKEN']], 'held' => false],
        ],
        ...$changed,
    ]];
}

/**
 * What a stack answers a change to a setting with, changed where a case says.
 *
 * @param  array<mixed>         $change
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function whatAStackSaysOfAChangeToASetting(array $change = [], array $changed = []): array
{
    return whatAStackSaysOfItsSettings([
        'rehearsed' => true,
        'change' => [
            'setting' => 'vpn.countries',
            'value' => 'Germany',
            'was' => 'Belgium',
            'regenerates' => ['gluetun'],
            'restarts' => ['gluetun', 'qbittorrent'],
            'applied' => false,
            'agreement' => 'settings-set:vpn.countries:7',
            ...$change,
        ],
        ...$changed,
    ]);
}

/** The listing that payload stands for, as the fake is handed it. */
function theSameSettingsTheServicesRead(): TheServicesSettings
{
    return TheServicesSettings::of(
        TheSettingsRead::these(
            ASetting::read('vpn.countries', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('gluetun'), 'SERVER_COUNTRIES', ASettingValue::value('Netherlands'))), ASettingValue::value('Belgium')),
            ASetting::read('domain', WhoseASettingIs::theCores(), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('caddy'), 'DOMAIN', ASettingValue::nothing()), AServiceReadingIt::of(ServiceId::called('gluetun'), 'PUBLIC_HOST', ASettingValue::nothing())), ASettingValue::nothing()),
        ),
        TheSecretsRead::these(
            ASecret::read('vpn.key', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('gluetun'), 'WIREGUARD_PRIVATE_KEY', ASettingValue::nothing())), held: true, from: WhereASecretCameFrom::Operator),
            ASecret::read('proxy.token', WhoseASettingIs::theCores(), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('caddy'), 'TOKEN', ASettingValue::nothing())), held: false, from: WhereASecretCameFrom::Unsaid),
        ),
    );
}

/** The rehearsal that change payload stands for, as the fake is handed it. */
function theSameRehearsalOfASetting(): TheSettingAsChanged
{
    return TheSettingAsChanged::said(
        ASettingChange::toHold('vpn.countries', 'Germany'),
        ASettingValue::value('Belgium'),
        WhatTheChangeReaches::said(Services::these(ServiceId::called('gluetun')), Services::these(ServiceId::called('gluetun'), ServiceId::called('qbittorrent')), '', ''),
        WhatTheSettingRunDid::Rehearsed,
        AnOffer::named('settings-set:vpn.countries:7'),
        theSameSettingsTheServicesRead(),
    );
}

/**
 * The refusal a stack answers a change to a setting that moved with.
 *
 * @return array<string, mixed>
 */
function aSettingThatMoved(): array
{
    return ['api_version' => 1, 'kind' => 'error', 'data' => [
        'code' => 'SETTING-5',
        'severity' => 'error',
        'state' => 'guided',
        'summary' => 'vpn.countries changed since it was read',
        'meaning' => 'A change agreed against an old value would write over one nobody was shown.',
        'remedies' => [['action' => 'Read the settings again and change it from there']],
    ]];
}

/** The adapter, answered with these responses in turn. */
function aTunerAnswered(MockResponse ...$answered): SettingWhatServicesRead
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Tuners(new PinnedClients(), SequencedEntropy::counting());
}

/** What a stack answers a change it took on with, naming the work. */
function aChangeToASettingTakenOn(string $job): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'settings-set', 'job' => $job]]), 202);
}

/** One reader, folded. */
function whatAReaderSays(AServiceReadingIt $reader): string
{
    return sprintf('%s:%s%s', $reader->service()->named(), $reader->variable(), $reader->default()->isHeld() ? sprintf('=%s', $reader->default()->said()) : '');
}

/** Every service reading one setting or secret, folded. */
function theReadersSaid(TheServicesReadingIt $readers): string
{
    $said = [];

    foreach ($readers as $reader) {
        $said[] = whatAReaderSays($reader);
    }

    return implode(',', $said);
}

/** Whose one setting or secret is, folded. */
function whoseItIsSaid(WhoseASettingIs $whose): string
{
    return $whose->either(
        core: static fn(): TheWordCarriedOut => new TheWordCarriedOut('core'),
        namespace: static fn(string $namespace): TheWordCarriedOut => new TheWordCarriedOut($namespace),
    )->said;
}

/** Every setting and secret, folded to one line. */
function everySettingSaid(TheServicesSettings $settings): string
{
    $said = [];

    foreach ($settings->settings() as $setting) {
        $said[] = sprintf(
            '%s(%s)[%s]%s',
            $setting->name(),
            whoseItIsSaid($setting->whose()),
            theReadersSaid($setting->readers()),
            $setting->value()->isHeld() ? sprintf('=%s', $setting->value()->said()) : '',
        );
    }

    foreach ($settings->secrets() as $secret) {
        $said[] = sprintf(
            'secret %s(%s)[%s]%s %s',
            $secret->name(),
            whoseItIsSaid($secret->whose()),
            theReadersSaid($secret->readers()),
            $secret->isHeld() ? ' held' : ' unset',
            $secret->from()->value,
        );
    }

    return implode(' | ', $said);
}

/** What reading came to, folded to one line. */
function whatTheSettingsCameTo(WhatWasFoundOfTheSettings $found): string
{
    return $found->either(
        found: static fn(TheServicesSettings $settings): TheWordCarriedOut => new TheWordCarriedOut(everySettingSaid($settings)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** Services named, folded. */
function theServicesSaid(Services $services): string
{
    $said = [];

    foreach ($services as $service) {
        $said[] = $service->named();
    }

    return implode(',', $said);
}

/**
 * What came of something asked, folded to one line.
 *
 * @param WhatBecameOfTheWork<TheSettingAsChanged> $became
 */
function whatTheSettingChangeCameTo(WhatBecameOfTheWork $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('following %s', $job->shown())),
        answered: static fn(TheSettingAsChanged $report): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s|%s|%s|%s|regenerates %s|restarts %s|%s|%s|%s|%d settings',
            $report->did()->value,
            $report->setting(),
            $report->value()->isHeld() ? $report->value()->said() : 'default',
            $report->was()->isHeld() ? $report->was()->said() : 'default',
            theServicesSaid($report->reaches()->regenerates()),
            theServicesSaid($report->reaches()->restarts()),
            $report->offer()->either(named: static fn(string $named): TheWordCarriedOut => new TheWordCarriedOut($named), none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no offer'))->said,
            $report->reaches()->consequence(),
            $report->reaches()->unrestarted(),
            iterator_count($report->settings()->settings()),
        )),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused: %s / %s', $why->summary(), $why->remedy())),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/** The yes to the rehearsal of Germany. */
function theYesToGermany(): ASettingChangeAgreed
{
    return ASettingChangeAgreed::after(theSameRehearsalOfASetting(), ASettingChange::toHold('vpn.countries', 'Germany'));
}

/** What the listing above folds to. */
const EVERY_SETTING_SAID = 'vpn.countries(gluetun)[gluetun:SERVER_COUNTRIES=Netherlands]=Belgium | domain(core)[caddy:DOMAIN,gluetun:PUBLIC_HOST] | secret vpn.key(gluetun)[gluetun:WIREGUARD_PRIVATE_KEY] held operator | secret proxy.token(core)[caddy:TOKEN] unset unsaid';

it('reads every setting and secret the services read, whose each is and who reads it, never a secret\'s value', function (): void {
    $ways = [
        'the fake' => static fn(): SettingWhatServicesRead => AStackThatTunesItsSettings::answering(theSameSettingsTheServicesRead(), WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended()),
        'the adapter' => static fn(): SettingWhatServicesRead => aTunerAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfItsSettings()))),
    ];

    foreach ($ways as $which => $build) {
        expect(whatTheSettingsCameTo($build()->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith())))->toBe(EVERY_SETTING_SAID, $which);
    }
});

it('reads none at all as an answer, not as a listing it could not read', function (): void {
    expect(whatTheSettingsCameTo(aTunerAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfItsSettings(['settings' => [], 'secrets' => [], 'unvetted' => ['overlay' => 'compose.override.yml', 'services' => ['gluetun']]]))))->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith())))
        ->toBe('');
});

it('comes away from reading with the obstacle where the stack could not be asked or read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatTheSettingsCameTo(aTunerAnswered($answered)->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith())))->toBe($kind->name)
        ->and(whatTheSettingsCameTo(AStackThatTunesItsSettings::sayingAtOnce(WhatWasFoundOfTheSettings::met(Obstacle::of($kind)), WhatBecameOfTheWork::ended())->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith())))->toBe($kind->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that failed' => [MockResponse::make('{"error":"still asking"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not one' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('reads a listing it cannot read whole as one it could not read, never as a shorter one', function (array $changed): void {
    expect(whatTheSettingsCameTo(aTunerAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfItsSettings($changed))))->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'no settings' => [['settings' => null]],
    'settings keyed rather than listed' => [['settings' => ['one' => ['name' => 'domain']]]],
    'a setting that is a word' => [['settings' => ['domain']]],
    'a setting with no name' => [['settings' => [['name' => ' ', 'whose' => ['of' => 'core'], 'readers' => []]]]],
    'a setting nobody owns' => [['settings' => [['name' => 'domain', 'readers' => []]]]],
    'an owner of a kind this app does not read' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'household'], 'readers' => []]]]],
    'a namespace with no name' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'namespace'], 'readers' => []]]]],
    'readers that are a word' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => 'caddy']]]],
    'readers keyed rather than listed' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => ['caddy' => ['service' => 'caddy', 'variable' => 'DOMAIN']]]]]],
    'a reader that is a word' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => ['caddy']]]]],
    'a reader with no service' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => [['variable' => 'DOMAIN']]]]]],
    'a reader with no variable' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => [['service' => 'caddy']]]]]],
    'a default that is not text' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => [['service' => 'caddy', 'variable' => 'DOMAIN', 'default' => 443]]]]]],
    'a value that is not text' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => [], 'value' => false]]]],
    'no secrets' => [['secrets' => 'none']],
    'a secret that is a word' => [['secrets' => ['vpn.key']]],
    'a secret with no word on whether it is held' => [['secrets' => [['name' => 'vpn.key', 'whose' => ['of' => 'core'], 'readers' => []]]]],
    'a secret from somewhere this app does not read' => [['secrets' => [['name' => 'vpn.key', 'whose' => ['of' => 'core'], 'readers' => [], 'held' => true, 'from' => 'neighbour']]]],
]);

it('reads a payload that is not a table as a listing it could not read', function (): void {
    expect(whatTheSettingsCameTo(aTunerAnswered(MockResponse::make((string) json_encode([...whatAStackSaysOfItsSettings(), 'data' => 'not a table'])))->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith())))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
});

it('reads a setting set to nothing apart from one left as it came', function (): void {
    $empty = aTunerAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfItsSettings(['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => [], 'value' => '']], 'secrets' => []]))))
        ->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith());
    $unset = aTunerAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfItsSettings(['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => [], 'value' => null]], 'secrets' => []]))))
        ->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith());

    expect(whatTheSettingsCameTo($empty))->toBe('domain(core)[]=')
        ->and(whatTheSettingsCameTo($unset))->toBe('domain(core)[]');
});

it('reads where a secret came from in each of the stack\'s words', function (string $from, WhereASecretCameFrom $said): void {
    $read = aTunerAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfItsSettings(['settings' => [], 'secrets' => [['name' => 'vpn.key', 'whose' => ['of' => 'core'], 'readers' => [], 'held' => true, 'from' => $from]]]))))
        ->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith());

    expect(whatTheSettingsCameTo($read))->toBe(sprintf('secret vpn.key(core)[] held %s', $said->value));
})->with([
    'the operator' => ['operator', WhereASecretCameFrom::Operator],
    'a recipe' => ['recipe', WhereASecretCameFrom::Recipe],
    'a grant' => ['granted', WhereASecretCameFrom::Granted],
    'minted' => ['minted', WhereASecretCameFrom::Minted],
]);

it('comes away from rehearsing with the job the stack named', function (): void {
    $ways = [
        'the fake' => static fn(): SettingWhatServicesRead => AStackThatTunesItsSettings::answering(theSameSettingsTheServicesRead(), WhatBecameOfTheWork::ended(), WhatBecameOfTheWork::ended()),
        'the adapter' => static fn(): SettingWhatServicesRead => aTunerAnswered(aChangeToASettingTakenOn(AStackThatTunesItsSettings::THE_REHEARSAL)),
    ];

    foreach ($ways as $which => $build) {
        expect(whatTheSettingChangeCameTo($build()->rehearse(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), ASettingChange::toHold('vpn.countries', 'Germany'))))
            ->toBe(sprintf('following %s', AStackThatTunesItsSettings::THE_REHEARSAL), $which);
    }
});

it('reads what the work came to, the rehearsal labelled as one with what it regenerates and restarts', function (): void {
    $ways = [
        'the fake' => static fn(): SettingWhatServicesRead => AStackThatTunesItsSettings::answering(theSameSettingsTheServicesRead(), WhatBecameOfTheWork::answered(theSameRehearsalOfASetting()), WhatBecameOfTheWork::ended()),
        'the adapter' => static fn(): SettingWhatServicesRead => aTunerAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfAChangeToASetting()))),
    ];

    foreach ($ways as $which => $build) {
        expect(whatTheSettingChangeCameTo($build()->whatBecameOf(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), Job::named(AStackThatTunesItsSettings::THE_REHEARSAL))))
            ->toBe('rehearsed|vpn.countries|Germany|Belgium|regenerates gluetun|restarts gluetun,qbittorrent|settings-set:vpn.countries:7|||2 settings', $which);
    }
});

it('reads a written change back to the default, with what it costs and why nothing restarted', function (): void {
    $written = aTunerAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfAChangeToASetting(
        ['value' => null, 'was' => null, 'applied' => true, 'restarts' => [], 'consequence' => ' The tunnel drops for a moment ', 'unrestarted' => ' gluetun was stopped '],
        ['rehearsed' => false],
    ))));

    expect(whatTheSettingChangeCameTo($written->whatBecameOf(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), Job::named('a-job'))))
        ->toBe('applied|vpn.countries|default|default|regenerates gluetun|restarts |settings-set:vpn.countries:7|The tunnel drops for a moment|gluetun was stopped|2 settings');
});

it('reads a run that neither rehearsed nor wrote as one that wrote nothing', function (): void {
    $unwritten = aTunerAnswered(MockResponse::make((string) json_encode(whatAStackSaysOfAChangeToASetting(['applied' => false], ['rehearsed' => false]))));

    expect(whatTheSettingChangeCameTo($unwritten->whatBecameOf(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), Job::named('a-job'))))
        ->toStartWith(WhatTheSettingRunDid::NotWritten->value);
});

it('says a job still running is still underway, and one the stack no longer has is ended', function (): void {
    $running = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['action' => 'settings-set', 'job' => 'a-job', 'state' => 'running']]), 202);
    $forgotten = MockResponse::make('{"error":"no such job"}', 404);

    expect(whatTheSettingChangeCameTo(aTunerAnswered($running)->whatBecameOf(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), Job::named('a-job'))))->toBe('following a-job')
        ->and(whatTheSettingChangeCameTo(aTunerAnswered($forgotten)->whatBecameOf(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), Job::named('a-job'))))->toBe('ended');
});

it('reads a refusal as the stack\'s own sentence and remedy, whether it refused the asking or the work', function (): void {
    $refused = MockResponse::make((string) json_encode(aSettingThatMoved()), 409);
    $said = 'refused: vpn.countries changed since it was read / Read the settings again and change it from there';

    expect(whatTheSettingChangeCameTo(aTunerAnswered($refused)->carryOut(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), theYesToGermany())))->toBe($said)
        ->and(whatTheSettingChangeCameTo(aTunerAnswered($refused)->whatBecameOf(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), Job::named('a-job'))))->toBe($said);
});

it('comes away with the obstacle where the stack could not be asked or its work read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatTheSettingChangeCameTo(aTunerAnswered($answered)->rehearse(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), ASettingChange::backToDefault('vpn.countries'))))->toBe($kind->name)
        ->and(whatTheSettingChangeCameTo(aTunerAnswered($answered)->whatBecameOf(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), Job::named('a-job'))))->toBe($kind->name)
        ->and(whatTheSettingChangeCameTo(AStackThatTunesItsSettings::sayingAtOnce(WhatWasFoundOfTheSettings::found(theSameSettingsTheServicesRead()), WhatBecameOfTheWork::met(Obstacle::of($kind)))->carryOut(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), theYesToGermany())))->toBe($kind->name);
})->with([
    'a refused session' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'a stack that failed' => [MockResponse::make('{"error":"still asking"}', 500), KindOfObstacle::StackDidNotAnswer],
    'an answer that is not one' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
]);

it('reads work that came back unreadable as an answer it could not read', function (array $change, array $changed): void {
    $finished = MockResponse::make((string) json_encode(whatAStackSaysOfAChangeToASetting($change, $changed)));

    expect(whatTheSettingChangeCameTo(aTunerAnswered($finished)->whatBecameOf(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), Job::named('a-job'))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'no change at all' => [[], ['change' => null]],
    'a change to no setting' => [['setting' => ''], []],
    'a value that is not text' => [['value' => 3], []],
    'what it was, as a list' => [['was' => ['Belgium']], []],
    'restarts that are a word' => [['restarts' => 'gluetun'], []],
    'restarts keyed rather than listed' => [['restarts' => ['first' => 'gluetun']], []],
    'a restart that is not a name' => [['restarts' => [7]], []],
    'a restart named blank' => [['restarts' => [' ']], []],
    'no word on whether it was written' => [['applied' => null], []],
    'no agreement to carry back' => [['agreement' => ' '], []],
    'no word on whether it was a rehearsal' => [[], ['rehearsed' => 'perhaps']],
    'a listing beside it that cannot be read' => [[], ['settings' => [['name' => 'domain']]]],
]);

/**
 * Where a recorded request went and what it carried.
 *
 * @return array<mixed>
 */
function whatWasSentAboutASetting(Response $recorded): array
{
    $body = $recorded->getPendingRequest()->body()?->all();

    return [
        'to' => $recorded->getPendingRequest()->getRequest()->resolveEndpoint(),
        ...(is_array($body) ? $body : []),
    ];
}

it('rehearses as a dry run carrying no offer, and the yes carries the offer that rehearsal named', function (): void {
    $tuners = aTunerAnswered(aChangeToASettingTakenOn('a'), aChangeToASettingTakenOn('b'));

    $tuners->rehearse(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), ASettingChange::toHold('vpn.countries', 'Germany'));
    $tuners->carryOut(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), theYesToGermany());

    [$rehearsal, $yes] = array_map(whatWasSentAboutASetting(...), MockClient::global()->getRecordedResponses());

    expect($rehearsal)->toMatchArray(['to' => '/api/actions/settings-set', 'dry_run' => true, 'setting' => 'vpn.countries', 'value' => 'Germany'])
        ->and($rehearsal['offer'] ?? null)->toBeNull()
        ->and($yes)->toMatchArray(['to' => '/api/actions/settings-set', 'setting' => 'vpn.countries', 'value' => 'Germany', 'offer' => 'settings-set:vpn.countries:7'])
        ->and($yes['dry_run'] ?? false)->toBeFalse();
});

it('puts a setting back to its default with settings-clear, naming no value', function (): void {
    $tuners = aTunerAnswered(aChangeToASettingTakenOn('a'), aChangeToASettingTakenOn('b'));
    $back = ASettingChange::backToDefault('vpn.countries');
    $rehearsal = TheSettingAsChanged::said($back, ASettingValue::value('Belgium'), WhatTheChangeReaches::said(Services::none(), Services::none(), '', ''), WhatTheSettingRunDid::Rehearsed, AnOffer::none(), theSameSettingsTheServicesRead());

    $tuners->rehearse(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), $back);
    $tuners->carryOut(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), ASettingChangeAgreed::after($rehearsal, $back));

    [$rehearsed, $yes] = array_map(whatWasSentAboutASetting(...), MockClient::global()->getRecordedResponses());

    expect($rehearsed)->toMatchArray(['to' => '/api/actions/settings-clear', 'dry_run' => true, 'setting' => 'vpn.countries'])
        ->and($yes)->toMatchArray(['to' => '/api/actions/settings-clear', 'setting' => 'vpn.countries'])
        ->and(array_key_exists('value', $yes))->toBeFalse()
        ->and($yes['offer'] ?? null)->toBeNull();
});

it('the fake remembers each reading, each change rehearsed and agreed to, and each handle followed', function (): void {
    $tuning = AStackThatTunesItsSettings::answering(theSameSettingsTheServicesRead(), WhatBecameOfTheWork::answered(theSameRehearsalOfASetting()), WhatBecameOfTheWork::ended());
    $change = ASettingChange::toHold('vpn.countries', 'Germany');
    $yes = theYesToGermany();
    $tuning->readOn(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith());
    $tuning->rehearse(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), $change);
    $tuning->carryOut(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), $yes);
    $became = $tuning->whatBecameOf(aStackWhoseSettingsAreRead(), theSessionTheSettingsAreReadWith(), Job::named(AStackThatTunesItsSettings::THE_CHANGE));

    expect($tuning->askings())->toBe(1)
        ->and($tuning->rehearsed())->toBe([$change])
        ->and($tuning->carriedOut())->toBe([$yes])
        ->and(array_map(static fn(Job $job): string => $job->shown(), $tuning->followed()))->toBe([AStackThatTunesItsSettings::THE_CHANGE])
        ->and(whatTheSettingChangeCameTo($became))->toBe('ended');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SettingsEnvelope', whatAStackSaysOfItsSettings()))
        ->toBe([], "The listing this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('SettingsEnvelope', whatAStackSaysOfAChangeToASetting()))
        ->toBe([], "The change this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('ErrorEnvelope', aSettingThatMoved()))
        ->toBe([], "The refusal this suite stands in for a stack with is not one a stack would send.\n");
});
