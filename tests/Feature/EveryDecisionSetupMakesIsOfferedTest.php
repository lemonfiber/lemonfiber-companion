<?php

declare(strict_types=1);

use Illuminate\Contracts\Translation\Translator;
use Lemonfiber\Sdk\Generated\ConfigEnvelope;
use Modules\Dx\Internal\WhatTheContractDeclares;
use Modules\Kernel\Api\ADecision;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhatSetupDecided as TheDecisions;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Operator\Internal\HowADecisionIsChanged;
use Modules\Operator\Internal\Screens\WhatSetupDecided;
use Modules\Operator\Internal\ViewModels\ADecisionAsShown;
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

/** Decisions the app offers to change through the stack's review. */
const DECISIONS_OFFERED = [
    'DATA_ROOT', 'MEDIA_SERVER_MODE', 'LEMONFIBER_USENET', 'LEMONFIBER_TORRENT', 'LEMONFIBER_VPN', 'PUID', 'PGID', 'VPN_PROVIDER',
    'INDEXER_URL', 'USENET_HOST', 'USENET_PORT', 'USENET_TLS', 'LEMONFIBER_FRONT_DOOR', 'TZ', 'LEMONFIBER_LOCALE',
];

/** Decisions the app names as changed at the machine, each with the requirement that excuses it. */
const DECISIONS_EXCUSED = [
    'INDEXER_APIKEY' => 'N1-R83: a credential is typed at the machine (N2-R12)',
    'USENET_USER' => 'N1-R83: a credential is typed at the machine (N2-R12)',
    'USENET_PASS' => 'N1-R83: a credential is typed at the machine (N2-R12)',
];

/**
 * Every decision key the contract declares, read off the generated envelope.
 *
 * @return list<string>
 */
function everyDecisionTheContractDeclares(): array
{
    $declared = (string) new ReflectionClass(ConfigEnvelope::class)->getDocComment();
    preg_match('/@phpstan-type\sDecisionKey\s(.+)/', $declared, $found);

    return array_map(static fn(string $key): string => trim($key, "' "), WhatTheContractDeclares::alternatives(trim($found[1] ?? '')));
}

/** The decided screen over these decisions, with the settings store listing each decision under its own name. */
function theDecidedScreenOver(TheDecisions $decided, ?AStackThatTunesItsSettings $tuning = null, ?AStackToldToChangeSomething $adjusting = null): WhatSetupDecided
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $stored = [];

    foreach ($decided as $decision) {
        $stored[] = ASetting::read($decision->key, WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing());
    }

    $screen = new WhatSetupDecided(
        AStackThatSaysWhatSetupDecided::with($decided),
        $adjusting ?? AStackToldToChangeSomething::saying(WhereTheChangeStands::at(ProposedChange::of('DATA_ROOT', '/srv', WhatItHoldsNow::nothingYet(), Cost::Cheap), Stance::Pending, WhatAChangeComesTo::nothing())),
        $tuning ?? AStackThatTunesItsSettings::sayingAtOnce(WhatWasFoundOfTheSettings::found(TheServicesSettings::of(TheSettingsRead::these(...$stored), TheSecretsRead::these())), WhatBecameOfTheWork::noOutcome()),
        AStackThatHoldsCredentials::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        AStackThatHosts::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer)),
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        AShareSheetThatWasOffered::working(),
        app(Translator::class),
        AJobThatSays::nothing(),
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);
    WhatTheDeviceWouldDraw::onTheFourthFrame($screen);

    return $screen;
}

/**
 * How the screen offers each decision, by key.
 *
 * @return array<string, HowADecisionIsChanged>
 */
function howEachDecisionIsOffered(TheDecisions $decided): array
{
    $how = [];

    foreach (theDecidedScreenOver($decided)->answer()->decisions as $row) {
        $how[$row->key] = $row->how;
    }

    return $how;
}

it('classifies every decision the contract declares as offered or excused, and fails naming one it does not', function (): void {
    $declared = everyDecisionTheContractDeclares();
    $unclassified = array_values(array_diff($declared, [...DECISIONS_OFFERED, ...array_keys(DECISIONS_EXCUSED)]));

    expect($declared)->toHaveCount(18)
        ->and($unclassified)->toBe([], sprintf(
            "The stack lists these decisions and nobody here has said how the app offers them:\n  %s\n\n"
            . 'N1-R82 says every decision the stack lists is offered through its review, and N1-R83 names what '
            . 'is changed at the machine instead. Put it in DECISIONS_OFFERED where the screen offers it, or in '
            . "DECISIONS_EXCUSED with the requirement that excuses it.\n",
            implode("\n  ", $unclassified),
        ))
        ->and(array_values(array_diff([...DECISIONS_OFFERED, ...array_keys(DECISIONS_EXCUSED)], $declared)))->toBe([])
        ->and(array_intersect(DECISIONS_OFFERED, array_keys(DECISIONS_EXCUSED)))->toBe([]);
});

it('offers every decision it says it offers, kept in the configuration or in the settings store', function (): void {
    $inTheConfiguration = TheDecisions::beside(Settings::none(), ...array_map(static fn(string $key): ADecision => ADecision::inTheConfiguration($key, Cost::Cheap, 'what it affects', held: false, credential: false), DECISIONS_OFFERED));
    $inTheSettings = TheDecisions::beside(Settings::none(), ...array_map(static fn(string $key): ADecision => ADecision::inTheSettings($key, Cost::Cheap, 'what it affects', held: false, credential: false, setting: $key), DECISIONS_OFFERED));

    expect(array_unique(howEachDecisionIsOffered($inTheConfiguration), SORT_REGULAR))->toBe([DECISIONS_OFFERED[0] => HowADecisionIsChanged::Here])
        ->and(array_keys(howEachDecisionIsOffered($inTheConfiguration)))->toBe(DECISIONS_OFFERED)
        ->and(array_unique(howEachDecisionIsOffered($inTheSettings), SORT_REGULAR))->toBe([DECISIONS_OFFERED[0] => HowADecisionIsChanged::Here]);
});

it('offers the decisions the stack keeps in the settings store here, by settings-set under the name it gives, held or not', function (bool $held): void {
    $kept = ['VPN_PROVIDER' => 'gluetun:provider', 'LEMONFIBER_LOCALE' => 'LEMONFIBER_LOCALE'];
    $tuning = AStackThatTunesItsSettings::sayingAtOnce(WhatWasFoundOfTheSettings::found(TheServicesSettings::of(TheSettingsRead::these(), TheSecretsRead::these())), WhatBecameOfTheWork::noOutcome());
    $adjusting = AStackToldToChangeSomething::saying(WhereTheChangeStands::at(ProposedChange::of('DATA_ROOT', '/srv', WhatItHoldsNow::nothingYet(), Cost::Cheap), Stance::Pending, WhatAChangeComesTo::nothing()));
    $screen = theDecidedScreenOver(TheDecisions::beside(Settings::none(), ...array_map(static fn(string $key): ADecision => ADecision::inTheSettings($key, Cost::Cheap, 'what it affects', held: $held, credential: false, setting: $kept[$key]), array_keys($kept))), $tuning, $adjusting);

    foreach (array_keys($kept) as $key) {
        $screen->change($key);
        $screen->typed = 'chosen';
        $screen->wouldBe($key);
    }

    expect(array_values(array_intersect(DECISIONS_OFFERED, array_keys($kept))))->toBe(array_keys($kept))
        ->and(array_map(static fn(ADecisionAsShown $row): array => [$row->key, $row->how, $row->setting], $screen->answer()->decisions))->toBe([
            ['VPN_PROVIDER', HowADecisionIsChanged::Here, 'gluetun:provider'],
            ['LEMONFIBER_LOCALE', HowADecisionIsChanged::Here, 'LEMONFIBER_LOCALE'],
        ])
        ->and(array_map(ASettingChangeAsReported::said(...), $tuning->rehearsed()))->toBe(['settings-set gluetun:provider=chosen', 'settings-set LEMONFIBER_LOCALE=chosen'])
        ->and($adjusting->rehearsals())->toBe(0);
})->with(['held' => [true], 'not held' => [false]]);

it('names every decision it excuses as changed at the machine, with the command, and never offers it', function (): void {
    $screen = theDecidedScreenOver(TheDecisions::beside(Settings::none(), ...array_map(static fn(string $key): ADecision => ADecision::inTheConfiguration($key, Cost::Cheap, 'what it affects', held: false, credential: true), array_keys(DECISIONS_EXCUSED))));

    expect(array_map(static fn(ADecisionAsShown $row): array => [$row->key, $row->how, $row->command], $screen->answer()->decisions))->toBe(array_map(
        static fn(string $key): array => [$key, HowADecisionIsChanged::AtTheMachine, sprintf('lemonfiber config set %s <value>', $key)],
        array_keys(DECISIONS_EXCUSED),
    ));

    foreach (array_keys(DECISIONS_EXCUSED) as $key) {
        $screen->change($key);

        expect($screen->changing())->toBe('');
    }
});
