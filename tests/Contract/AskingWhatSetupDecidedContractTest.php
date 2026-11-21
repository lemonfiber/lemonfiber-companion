<?php

declare(strict_types=1);

use Modules\Kernel\Api\ADecision;
use Modules\Kernel\Api\ADecisionSaysNothing;
use Modules\Kernel\Api\AskingWhatSetupDecided;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Kernel\Api\WhatSetupDecided;
use Modules\Kernel\Api\WhatWasFoundOfTheDecisions;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Sdk\Api\Arrangements;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatSetupDecided;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** Three decisions: one held in the configuration, one a credential nobody wrote, one kept in the settings store. */
function theSameDecisions(): WhatSetupDecided
{
    return WhatSetupDecided::beside(
        Settings::of(Setting::called('DATA_ROOT', WhatASettingHolds::shown('/srv/media'), WhoPutItThere::bundled())),
        ADecision::inTheConfiguration('DATA_ROOT', Cost::Consequential, 'where every download lives', held: true, credential: false),
        ADecision::inTheConfiguration('USENET_PASS', Cost::Cheap, 'the password the account is reached with', held: false, credential: true),
        ADecision::inTheSettings('VPN_PROVIDER', Cost::Consequential, 'which provider carries the torrents', held: true, credential: false, setting: 'gluetun:provider'),
    );
}

/**
 * The body a stack sends for those decisions.
 *
 * @param  list<mixed>  $decisions
 * @return array<string, mixed>
 */
function whatAStackSendsAboutItsDecisions(array $decisions = [
    ['key' => 'DATA_ROOT', 'cost' => 'consequential', 'affects' => 'where every download lives', 'held' => true, 'credential' => false],
    ['key' => 'USENET_PASS', 'cost' => 'cheap', 'affects' => 'the password the account is reached with', 'held' => false, 'credential' => true, 'setting' => null],
    ['key' => 'VPN_PROVIDER', 'cost' => 'consequential', 'affects' => 'which provider carries the torrents', 'held' => true, 'credential' => false, 'setting' => 'gluetun:provider'],
]): array
{
    return [
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => false,
            'decisions' => $decisions,
            'settings' => [['key' => 'DATA_ROOT', 'value' => '/srv/media', 'secret' => false, 'origin' => ['origin' => 'bundled']]],
        ],
    ];
}

/** @param array<string, mixed> $body */
function theDecisionsAdapterAnswering(array $body, int $status = 200): AskingWhatSetupDecided
{
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make($body, $status)]);

    return new Arrangements(new PinnedClients());
}

/** What a stack answered, as one string, whichever arm it took. */
function howTheDecisionsReadAsText(AskingWhatSetupDecided $asking): string
{
    return $asking->decidedOn(TheHealthScreenOfTheLoft::theStackBeingLookedAt(), Session::of('a-session-not-a-secret'))->either(
        found: static function (WhatSetupDecided $decided): TheWordCarriedOut {
            $rows = [];

            foreach ($decided as $decision) {
                $rows[] = sprintf('%s/%s/%s/%s/%s/%s', $decision->key, $decision->cost->value, $decision->affects, $decision->held ? 'held' : 'unheld', $decision->credential ? 'credential' : 'shown', $decision->setting ?? 'config-set');
            }

            foreach ($decided->settings() as $setting) {
                $rows[] = sprintf('%s=%s', $setting->key, $setting->holds->either(shown: static fn(string $value): TheWordCarriedOut => new TheWordCarriedOut($value), withheld: static fn(): TheWordCarriedOut => new TheWordCarriedOut('withheld'))->said);
            }

            return new TheWordCarriedOut(sprintf('found:%s', implode(',', $rows)));
        },
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met:%s', $why->kind()->value)),
    )->said;
}

it('the fake and the adapter read the same decisions, in the order sent, beside the settings', function (): void {
    $said = 'found:DATA_ROOT/consequential/where every download lives/held/shown/config-set,'
        . 'USENET_PASS/cheap/the password the account is reached with/unheld/credential/config-set,'
        . 'VPN_PROVIDER/consequential/which provider carries the torrents/held/shown/gluetun:provider,'
        . 'DATA_ROOT=/srv/media';

    expect(howTheDecisionsReadAsText(theDecisionsAdapterAnswering(whatAStackSendsAboutItsDecisions())))->toBe($said)
        ->and(howTheDecisionsReadAsText(AStackThatSaysWhatSetupDecided::with(theSameDecisions())))->toBe($said);
});

it('the fake and the adapter both refuse rather than answer with nothing', function (): void {
    expect(howTheDecisionsReadAsText(theDecisionsAdapterAnswering(['nothing' => 'the contract knows'], 500)))->toStartWith('met:')
        ->and(howTheDecisionsReadAsText(AStackThatSaysWhatSetupDecided::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('met:no_answer');
});

it('refuses a decision it cannot read rather than leaving it off the list', function (mixed $decision): void {
    expect(howTheDecisionsReadAsText(theDecisionsAdapterAnswering(whatAStackSendsAboutItsDecisions([$decision]))))->toBe('met:answer_unreadable');
})->with([
    'a cost outside the contract' => [['key' => 'DATA_ROOT', 'cost' => 'free', 'affects' => 'x', 'held' => true, 'credential' => false]],
    'no affects' => [['key' => 'DATA_ROOT', 'cost' => 'cheap', 'held' => true, 'credential' => false]],
    'held as text' => [['key' => 'DATA_ROOT', 'cost' => 'cheap', 'affects' => 'x', 'held' => 'true', 'credential' => false]],
    'no credential' => [['key' => 'DATA_ROOT', 'cost' => 'cheap', 'affects' => 'x', 'held' => true]],
    'a blank key' => [['key' => '  ', 'cost' => 'cheap', 'affects' => 'x', 'held' => true, 'credential' => false]],
    'a blank setting' => [['key' => 'TZ', 'cost' => 'cheap', 'affects' => 'x', 'held' => true, 'credential' => false, 'setting' => ' ']],
    'not a table' => ['DATA_ROOT'],
]);

it('refuses an answer with no decisions at all rather than reading it as none', function (): void {
    $body = ['api_version' => 1, 'kind' => 'config', 'data' => ['changed' => false, 'rehearsed' => false, 'settings' => []]];

    expect(howTheDecisionsReadAsText(theDecisionsAdapterAnswering($body)))->toBe('met:answer_unreadable')
        ->and(howTheDecisionsReadAsText(theDecisionsAdapterAnswering(whatAStackSendsAboutItsDecisions([]))))->toBe('found:DATA_ROOT=/srv/media');
});

it('a decision with an empty part is refused where it is made', function (string $key, string $affects, string $setting): void {
    expect(static fn(): ADecision => ADecision::inTheSettings($key, Cost::Cheap, $affects, held: false, credential: false, setting: $setting))->toThrow(ADecisionSaysNothing::class);
})->with([
    'key' => [' ', 'x', 'y'],
    'affects' => ['K', '', 'y'],
    'setting' => ['K', 'x', ''],
]);

it('reaches the arm the answer calls for, and hands it what it was given', function (): void {
    $decided = theSameDecisions();
    $found = WhatWasFoundOfTheDecisions::found($decided)->either(found: static fn(WhatSetupDecided $was): TheWordCarriedOut => new TheWordCarriedOut($was === $decided ? 'same' : 'other'), met: static fn(): TheWordCarriedOut => new TheWordCarriedOut('met'));
    $met = WhatWasFoundOfTheDecisions::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))->either(found: static fn(): TheWordCarriedOut => new TheWordCarriedOut('found'), met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value));

    expect($found->said)->toBe('same')
        ->and($met->said)->toBe('no_answer')
        ->and(count($decided))->toBe(3)
        ->and(ADecision::inTheConfiguration(' TZ ', Cost::Cheap, ' x ', held: false, credential: false)->key)->toBe('TZ');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ConfigEnvelope', whatAStackSendsAboutItsDecisions()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
