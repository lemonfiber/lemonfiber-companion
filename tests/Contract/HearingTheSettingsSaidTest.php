<?php

declare(strict_types=1);

use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Kernel\Api\WhoseASettingIs;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\TheStreamListenedTo;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The Hearing contract for the settings the stream says, run against the adapter and against the fake.
//
// Apart from HearingContractTest for that file's length.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** What one wake met, or that it met nothing. */
function whatTheSettingsStreamMet(WhatWasHeard $heard): string
{
    return $heard->either(
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
        alive: static fn(): TheWordCarriedOut => new TheWordCarriedOut('alive'),
        said: static fn(): TheWordCarriedOut => new TheWordCarriedOut('a summary'),
        closed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('closed'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/**
 * The settings a stack's stream says, changed where a case says.
 *
 * @param  array<mixed>         $changed
 * @return array<string, mixed>
 */
function theSettingsSaid(array $changed = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'settings',
        'data' => [
            'rehearsed' => false,
            'settings' => [['name' => 'vpn.countries', 'whose' => ['of' => 'namespace', 'namespace' => 'gluetun'], 'readers' => [['service' => 'gluetun', 'variable' => 'SERVER_COUNTRIES', 'needs' => [], 'met' => true]], 'value' => 'Belgium']],
            'secrets' => [],
            'waiting' => [],
            ...$changed,
        ],
    ];
}

/**
 * One settings event as the core frames it.
 *
 * @param array<mixed> $changed
 */
function aSettingsEvent(array $changed = []): string
{
    return TheStreamListenedTo::anEvent('settings', json_encode(theSettingsSaid($changed), JSON_THROW_ON_ERROR));
}

/** Every setting what was heard says, as one line. */
function whatWasSettledAsAWord(WhatWasHeard $heard): string
{
    return $heard->theSettings(
        said: static function (TheServicesSettings $settings): TheWordCarriedOut {
            $said = [];

            foreach ($settings->settings() as $setting) {
                $said[] = sprintf('%s=%s', $setting->name(), $setting->value()->said());
            }

            return new TheWordCarriedOut(implode('|', $said));
        },
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing settled'),
    )->said;
}

it('hears the settings the stream says as a sign of life, only the last of them, and nothing settled where it says none', function (): void {
    $settled = TheServicesSettings::of(
        TheSettingsRead::these(ASetting::read('vpn.countries', WhoseASettingIs::theNamespaces('gluetun'), TheServicesReadingIt::these(AServiceReadingIt::of(ServiceId::called('gluetun'), 'SERVER_COUNTRIES', ASettingValue::nothing())), ASettingValue::value('Belgium'))),
        TheSecretsRead::these(),
    );

    foreach (TheStreamListenedTo::everyWay(
        [MockResponse::make(sprintf('%s%s', aSettingsEvent(['settings' => []]), aSettingsEvent()))],
        AStackThatSpeaksUp::thenEnding(...[WhatWasHeard::aSignOfLife()->settledAs($settled)]),
    ) as $which => $make) {
        $hearing = $make();

        expect(array_map(whatWasSettledAsAWord(...), [$hearing->howItIs(TheStreamListenedTo::aStack(), TheStreamListenedTo::theSession()), $hearing->howItIs(TheStreamListenedTo::aStack(), TheStreamListenedTo::theSession())]))
            ->toBe(['vpn.countries=Belgium', 'nothing settled'], $which);
    }
});

it('cannot hear settings it cannot read, and says so as an answer it could not read', function (array $changed): void {
    foreach (TheStreamListenedTo::everyWay(
        [MockResponse::make(aSettingsEvent($changed))],
        AStackThatSpeaksUp::thenEnding(...[WhatWasHeard::met(Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead))]),
    ) as $which => $make) {
        expect(whatTheSettingsStreamMet($make()->howItIs(TheStreamListenedTo::aStack(), TheStreamListenedTo::theSession())))->toBe(KindOfObstacle::AnswerCouldNotBeRead->name, $which);
    }
})->with([
    'settings that are not a list' => [['settings' => 'all of them']],
    'a setting with no name' => [['settings' => [['name' => ' ', 'whose' => ['of' => 'core'], 'readers' => []]]]],
    'a reader with no service' => [['settings' => [['name' => 'domain', 'whose' => ['of' => 'core'], 'readers' => [['service' => ' ', 'variable' => 'DOMAIN']]]]]],
]);

it('stands in for a stack with the settings it says as the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SettingsEnvelope', theSettingsSaid()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
