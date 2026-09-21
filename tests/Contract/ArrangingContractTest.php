<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItIsSet;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Setting;
use Modules\Kernel\Api\SettingIsUnnamed;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatASettingHolds;
use Modules\Sdk\Api\Arrangements;
use Modules\Sdk\Api\Dials;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\SettingIsUnreadable;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatIsSet;
use Tests\Support\WhatTheContractAccepts;

// The Arranging contract, run against the adapter and against the fake.
//
// `G2`'s shape. The promise is that the listing is the stack's: the order it
// sent, the rows it sent, and the stack's own note where a value was withheld.
// A fake that sorted, or that wrote its own word for a withheld value, would
// let a screen pass against a listing no stack produces — and the screen's
// whole claim is that what is drawn is what came back.
//
// The second promise is the one absence hides: a stack holding nothing and a
// stack that would not answer arrive as the same blank and are opposite
// sentences on a screen. Both implementations have to keep them apart.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The machine whose settings are being read. */
function theMachineBeingRead(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session the operator is holding. */
function theSessionAnOperatorHolds(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * Two settings: one shown, one withheld.
 *
 * Both kinds in every reading, because the failure worth refusing is a fold
 * that treats them alike — and a listing of one kind cannot catch it.
 */
function theSameSettings(): Settings
{
    return Settings::of(
        Setting::called('LIBRARY_PATH', WhatASettingHolds::shown('/data/media')),
        Setting::called('API_KEY', WhatASettingHolds::withheld('set, not shown')),
    );
}

/**
 * The body a stack sends for those two settings.
 *
 * Built once and both asserted against the contract and handed to the
 * adapter, so the payload this suite passes against is the payload a stack
 * could actually send.
 *
 * @return array<string, mixed>
 */
function whatAStackSendsAboutItsSettings(): array
{
    return [
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => false,
            'settings' => [
                // `origin` is required and is a union of four arms. Two
                // different ones here rather than the same twice: a stand-in
                // that only ever shows one arm is a payload the contract
                // accepts and a stack would not send, which is the difference
                // this case exists to catch.
                [
                    'key' => 'LIBRARY_PATH',
                    'value' => '/data/media',
                    'secret' => false,
                    'origin' => ['origin' => 'operator'],
                ],
                [
                    'key' => 'API_KEY',
                    'value' => 'set, not shown',
                    'secret' => true,
                    'origin' => ['named' => 'plex', 'origin' => 'plugin'],
                ],
            ],
        ],
    ];
}

/** The same body, as the answer the adapter reads. */
function theConfigPayload(): MockResponse
{
    return MockResponse::make(whatAStackSendsAboutItsSettings());
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Closure(): Arranging>
 */
function everyWayOfReadingSettings(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Arranging => $why instanceof Obstacle
            ? AStackThatIsSet::met($why)
            : AStackThatIsSet::to(theSameSettings()),
        'the adapter' => static function () use ($answered): Arranging {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Arrangements(new PinnedClients());
        },
    ];
}

/**
 * What a stack answered, as one string, whichever arm it took.
 *
 * Every row is rendered through the fold, so a reading that reached a value
 * without saying what happens to a withheld one could not produce this string
 * at all.
 */
function howAStackReadsAsText(Arranging $arranging): string
{
    return $arranging->asItStands(theMachineBeingRead(), theSessionAnOperatorHolds())->either(
        told: static function (Settings $set): WhatAnOperatorCameAwayWith {
            $rows = [];

            foreach ($set as $one) {
                $rows[] = $one->holds->either(
                    shown: static fn(string $value): WhatOneRowSaid
                        => new WhatOneRowSaid(sprintf('%s=%s', $one->key, $value)),
                    withheld: static fn(string $note): WhatOneRowSaid
                        => new WhatOneRowSaid(sprintf('%s~%s', $one->key, $note)),
                )->said;
            }

            return new WhatAnOperatorCameAwayWith(sprintf('told:%s', implode(',', $rows)));
        },
        refused: static fn(Obstacle $why): WhatAnOperatorCameAwayWith
            => new WhatAnOperatorCameAwayWith(sprintf('refused:%s', $why->value)),
    )->said;
}

/** One row carried out of the fold. */
final readonly class WhatOneRowSaid
{
    public function __construct(public string $said) {}
}

/** One answer carried out of an `either()` arm. */
final readonly class WhatAnOperatorCameAwayWith
{
    public function __construct(public string $said) {}
}

foreach (everyWayOfReadingSettings(theConfigPayload()) as $name => $build) {
    it(sprintf('%s answers with the listing the stack sent, in order', $name), function () use ($build): void {
        expect(howAStackReadsAsText($build()))
            ->toBe('told:LIBRARY_PATH=/data/media,API_KEY~set, not shown');
    });

    it(sprintf('%s keeps a withheld value apart from a shown one', $name), function () use ($build): void {
        // The assertion the fold exists for. Both rows are strings on the
        // wire, and a reading that lost the difference would produce a
        // listing that looks right and prints a credential's note where a
        // value belongs — or, the other way round, a value where the note
        // belongs.
        expect(howAStackReadsAsText($build()))->toContain('API_KEY~')
            ->and(howAStackReadsAsText($build()))->not->toContain('API_KEY=');
    });
}

it('the fake and the adapter agree that a stack holding nothing has answered', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make([
        'api_version' => 1,
        'kind' => 'config',
        'data' => ['changed' => false, 'rehearsed' => false, 'settings' => []],
    ])]);

    expect(howAStackReadsAsText(new Arrangements(new PinnedClients())))->toBe('told:')
        ->and(howAStackReadsAsText(AStackThatIsSet::toNothing()))->toBe('told:');
});

it('the fake and the adapter both refuse rather than answer with nothing', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make(['nothing' => 'the contract knows'], 500)]);

    // The two absences that must not look alike. A stack that would not answer
    // and a stack holding nothing both arrive with no rows, and the string
    // above is what tells them apart for every screen downstream.
    expect(howAStackReadsAsText(new Arrangements(new PinnedClients())))
        ->not->toBe('told:')
        ->and(howAStackReadsAsText(AStackThatIsSet::met(Obstacle::StackDidNotAnswer)))
        ->toBe('refused:no_answer');
});

it('both ask about the stack they were given', function (): void {
    $fake = AStackThatIsSet::to(theSameSettings());
    $fake->asItStands(theMachineBeingRead(), theSessionAnOperatorHolds());

    expect($fake->askedAbout()?->id()->is(theMachineBeingRead()->id()))->toBeTrue()
        ->and($fake->askings())->toBe(1);
});

it('an unreadable listing is refused rather than shortened', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make([
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => false,
            'rehearsed' => false,
            // The second row has no `secret`, so this app cannot tell whether
            // its value may be printed. A reading that dropped the row would
            // present a short listing as a complete one, which is the one
            // thing this screen must never do.
            'settings' => [
                ['key' => 'LIBRARY_PATH', 'value' => '/data/media', 'secret' => false],
                ['key' => 'API_KEY', 'value' => 'set, not shown'],
            ],
        ],
    ])]);

    expect(howAStackReadsAsText(new Arrangements(new PinnedClients())))->toBe('refused:no_answer');
});

it('a setting with no name is refused rather than drawn nameless', function (): void {
    expect(static fn(): Setting => Setting::called('   ', WhatASettingHolds::shown('x')))
        ->toThrow(SettingIsUnnamed::class);
});

it('HowItIsSet reaches the arm the answer calls for, and hands it what it was given', function (): void {
    // The fold itself, both arms, so neither is reached only through a port.
    // Each arm names what it was handed rather than ignoring it, so the
    // assertion is that the right arm ran *and* that it was given the right
    // thing — an arm that fired with the wrong payload would pass a test that
    // only counted which one it was.
    $whichArm = static fn(HowItIsSet $how): string => $how->either(
        told: static fn(Settings $set): WhatOneRowSaid => new WhatOneRowSaid(
            sprintf('told:%d', count($set)),
        ),
        refused: static fn(Obstacle $why): WhatOneRowSaid => new WhatOneRowSaid(
            sprintf('refused:%s', $why->value),
        ),
    )->said;

    expect($whichArm(HowItIsSet::told(Settings::none())))->toBe('told:0')
        ->and($whichArm(HowItIsSet::told(theSameSettings())))->toBe('told:2')
        ->and($whichArm(HowItIsSet::refused(Obstacle::StackDidNotAnswer)))->toBe('refused:no_answer');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ConfigEnvelope', whatAStackSendsAboutItsSettings()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});

/**
 * A stack answering with whatever a test puts in `data`.
 *
 * Every refusal below is a shape the contract forbids, and each is reached
 * through the adapter rather than by calling the reader directly — a refusal
 * proven only against the reader is one the port might still swallow.
 *
 * @param array<string, mixed> $data
 */
function aStackAnsweringWith(array $data): Arranging
{
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make([
        'api_version' => 1,
        'kind' => 'config',
        'data' => $data,
    ])]);

    return new Arrangements(new PinnedClients());
}

it('refuses an answer with no settings at all rather than reading it as none', function (): void {
    // The two arrive as the same blank listing and are opposite: a stack with
    // nothing set says so with an empty list, which is accepted above. An
    // answer with no `settings` key is one this app could not read.
    expect(howAStackReadsAsText(aStackAnsweringWith(['changed' => false, 'rehearsed' => false])))
        ->toBe('refused:no_answer');
});

it('refuses settings that are not a list', function (): void {
    expect(howAStackReadsAsText(aStackAnsweringWith([
        'changed' => false, 'rehearsed' => false, 'settings' => 'all of them',
    ])))->toBe('refused:no_answer');
});

it('refuses a row that is not a setting, and says which row', function (): void {
    expect(howAStackReadsAsText(aStackAnsweringWith([
        'changed' => false, 'rehearsed' => false, 'settings' => ['LIBRARY_PATH'],
    ])))->toBe('refused:no_answer');

    // The position, not merely that it threw. A stack sending eighty settings
    // and one bad row is otherwise a message with nowhere to look — and the
    // refusal for a listing that is not a list would be wrong here, because
    // this one is.
    expect(static fn(): Settings => Dials::in(new Envelope(1, 'config', [
        'changed' => false, 'rehearsed' => false,
        'settings' => [
            ['key' => 'LIBRARY_PATH', 'value' => '/data/media', 'secret' => false],
            'API_KEY',
        ],
    ])))->toThrow(SettingIsUnreadable::class, 'position 1');
});

it('refuses a row with no key, and one whose key is not text', function (): void {
    expect(howAStackReadsAsText(aStackAnsweringWith([
        'changed' => false, 'rehearsed' => false,
        'settings' => [['value' => '/data/media', 'secret' => false]],
    ])))->toBe('refused:no_answer')
        ->and(howAStackReadsAsText(aStackAnsweringWith([
            'changed' => false, 'rehearsed' => false,
            'settings' => [['key' => 7, 'value' => '/data/media', 'secret' => false]],
        ])))->toBe('refused:no_answer');
});

it('refuses a row whose value is not text', function (): void {
    expect(howAStackReadsAsText(aStackAnsweringWith([
        'changed' => false, 'rehearsed' => false,
        'settings' => [['key' => 'PORT', 'value' => 8443, 'secret' => false]],
    ])))->toBe('refused:no_answer');
});

it('refuses a secret flag that is not a boolean rather than reading it for truth', function (): void {
    // The one worth spelling out. `"false"` is a non-empty string and true to
    // PHP, so a reading that took this for truthiness would mark a shown value
    // as withheld — or, with `"true"` absent and the field a `0`, print a
    // withheld note as a value.
    expect(howAStackReadsAsText(aStackAnsweringWith([
        'changed' => false, 'rehearsed' => false,
        'settings' => [['key' => 'API_KEY', 'value' => 'set, not shown', 'secret' => 'false']],
    ])))->toBe('refused:no_answer');
});

it('says which field was missing, for whoever has to find it', function (): void {
    // The refusal is a developer's, so it names the field: that is the only
    // thing that shortens the search, and it is why these are not translated.
    expect(static fn(): Settings => Dials::in(
        new Envelope(1, 'config', ['changed' => false, 'rehearsed' => false]),
    ))->toThrow(SettingIsUnreadable::class, '`settings`');
});
