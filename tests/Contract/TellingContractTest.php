<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnEventSetApart;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SetApart;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Telling;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Kernel\Api\WhetherItIsHeard;
use Modules\Sdk\Api\Heralds;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatItTells;
use Tests\Support\WhatTheContractAccepts;

// The Telling contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `OutgoingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose alert setting is asked for. */
function aStackThatSaysWhatItWakesYouFor(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameSetting(): WhatTheOperatorIsTold
{
    return WhatTheOperatorIsTold::byPreset('quiet', 'Only what needs you today', SetApart::of(
        AnEventSetApart::of('update-available', WhetherItIsHeard::Silenced),
        AnEventSetApart::of('disk-low', WhetherItIsHeard::Heard),
    ));
}

/**
 * The payload a stack sends for that setting.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysItTells(string $meansOfIt = 'Only what needs you today'): array
{
    return [
        'api_version' => 1,
        'kind' => 'alerts',
        'data' => [
            'preset' => 'quiet',
            'means' => $meansOfIt,
            'exceptions' => [['kind' => 'update-available', 'wanted' => false], ['kind' => 'disk-low', 'wanted' => true]],
            'changed' => false,
            'rehearsed' => false,
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Telling>
 */
function everyWayOfAskingWhatIsTold(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Telling => $why instanceof Obstacle
            ? AStackThatSaysWhatItTells::met($why)
            : AStackThatSaysWhatItTells::with(theSameSetting()),
        'the adapter' => static function () use ($answered): Telling {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Heralds(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheSettingTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the setting says, folded to one line, so two answers can be compared. */
function everythingTheSettingSays(Telling $telling): string
{
    return $telling->toldAbout(aStackThatSaysWhatItWakesYouFor(), Session::of('a-session-not-a-secret'))->either(
        told: static function (WhatTheOperatorIsTold $told): WhatTheSettingTurnedOutToSay {
            $exceptions = [];

            foreach ($told->exceptions() as $event) {
                $exceptions[] = sprintf('%s=%s', $event->kind(), $event->heard()->value);
            }

            return new WhatTheSettingTurnedOutToSay(sprintf('%s/%s/[%s]', $told->preset(), $told->means(), implode(',', $exceptions)));
        },
        met: static fn(Obstacle $why): WhatTheSettingTurnedOutToSay => new WhatTheSettingTurnedOutToSay($why->value),
    )->said;
}

it('N10-R8 — comes away with the preset, what it means and every exception', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysItTells()));

    foreach (everyWayOfAskingWhatIsTold($answered) as $which => $make) {
        expect(everythingTheSettingSays($make()))->toBe('quiet/Only what needs you today/[update-available=silenced,disk-low=heard]', $which);
    }
});

it('N10-R12 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatIsTold($answered, $why) as $which => $make) {
            expect(everythingTheSettingSays($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('N10-R12 — a setting this app cannot read is an obstacle, not an empty one', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItTells('  ')))]);

    expect(everythingTheSettingSays(new Heralds(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('AlertsEnvelope', whatAStackSaysItTells()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
