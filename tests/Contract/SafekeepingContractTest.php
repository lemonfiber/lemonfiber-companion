<?php

declare(strict_types=1);

use Modules\Kernel\Api\ACredentialHeld;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\Safekeeping;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatTheStoreProtects;
use Modules\Kernel\Api\WhatUsesIt;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhoMadeACredential;
use Modules\Sdk\Api\Keyholders;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatHoldsCredentials;
use Tests\Support\WhatTheContractAccepts;

// The Safekeeping contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `SelfCheckingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked what it holds. */
function aStackHoldingCredentials(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameCredentials(): TheCredentialsHeld
{
    return TheCredentialsHeld::of(
        WhatTheStoreProtects::said('Plain files readable only by you', Remarks::of('Another account on this machine'), Remarks::of('Anyone signed in as you')),
        ACredentialHeld::described('Indexer API key', WhereACredentialStands::Stale, WhoMadeACredential::Service, WhatUsesIt::of('sonarr', 'bazarr'), 'Not checked since it was written'),
        ACredentialHeld::described('Torrent client password', WhereACredentialStands::Active, WhoMadeACredential::Lemonfiber, WhatUsesIt::of(), ''),
    );
}

/**
 * The payload a stack sends for that, with a value revealed beside it that no reading may carry.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysItHolds(string $state = 'stale'): array
{
    return [
        'api_version' => 1,
        'kind' => 'credentials',
        'data' => [
            'held' => [
                [
                    'name' => 'Indexer API key',
                    'state' => $state,
                    'origin' => 'service',
                    'consumers' => ['sonarr', 'bazarr'],
                    'advisory' => 'Not checked since it was written',
                    'fingerprint' => 'a1b2c3',
                    'from' => ['origin' => 'bundled'],
                    'location' => '/srv/lemonfiber/secrets/indexer',
                    'setting' => 'indexer.api_key',
                ],
                [
                    'name' => 'Torrent client password',
                    'state' => 'active',
                    'origin' => 'lemonfiber',
                    'consumers' => [],
                    'from' => ['origin' => 'bundled'],
                    'location' => '/srv/lemonfiber/secrets/torrent',
                    'setting' => 'torrent.password',
                ],
            ],
            'protection' => [
                'summary' => 'Plain files readable only by you',
                'against' => ['Another account on this machine'],
                'not_against' => ['Anyone signed in as you'],
            ],
            'revealed' => ['name' => 'Indexer API key', 'value' => 'hunter2-never-drawn', 'warning' => 'This is shown once'],
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Safekeeping>
 */
function everyWayOfAskingWhatIsHeld(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Safekeeping => $why instanceof Obstacle
            ? AStackThatHoldsCredentials::met($why)
            : AStackThatHoldsCredentials::holding(theSameCredentials()),
        'the adapter' => static function () use ($answered): Safekeeping {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Keyholders(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheCredentialsTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the reading says, folded to one line, so two answers can be compared. */
function everythingTheCredentialsSay(Safekeeping $safekeeping): string
{
    return $safekeeping->heldOn(aStackHoldingCredentials(), Session::of('a-session-not-a-secret'))->either(
        found: static function (TheCredentialsHeld $held): WhatTheCredentialsTurnedOutToSay {
            $lines = [$held->protection()->summary(), implode(',', iterator_to_array($held->protection()->against(), preserve_keys: false)), implode(',', iterator_to_array($held->protection()->notAgainst(), preserve_keys: false))];

            foreach ($held as $credential) {
                $lines[] = sprintf(
                    '%s|%s|%s|%s|%s',
                    $credential->name(),
                    $credential->state()->value,
                    $credential->origin()->value,
                    implode(',', iterator_to_array($credential->consumers(), preserve_keys: false)),
                    $credential->advisory(),
                );
            }

            return new WhatTheCredentialsTurnedOutToSay(implode("\n", $lines));
        },
        met: static fn(Obstacle $why): WhatTheCredentialsTurnedOutToSay => new WhatTheCredentialsTurnedOutToSay($why->value),
    )->said;
}

it('comes away with every credential, where each stands, who made it, what uses it, and what the store protects against', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysItHolds()));

    foreach (everyWayOfAskingWhatIsHeld($answered) as $which => $make) {
        expect(everythingTheCredentialsSay($make()))->toBe(
            "Plain files readable only by you\nAnother account on this machine\nAnyone signed in as you\n"
            . "Indexer API key|stale|service|sonarr,bazarr|Not checked since it was written\n"
            . 'Torrent client password|active|lemonfiber||',
            $which,
        );
    }
});

it('never carries a value the stack revealed beside the list', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItHolds()))]);

    expect(everythingTheCredentialsSay(new Keyholders(new PinnedClients())))->not->toContain('hunter2-never-drawn');
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatIsHeld($answered, $why) as $which => $make) {
            expect(everythingTheCredentialsSay($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('a state this app cannot read is an obstacle, never a credential drawn as working', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItHolds('expired')))]);

    expect(everythingTheCredentialsSay(new Keyholders(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('asks the credentials endpoint, and nothing that would reveal or replace one', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItHolds()))]);

    everythingTheCredentialsSay(new Keyholders(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toEndWith('/api/credentials')
        ->and($sent?->query()->all())->toBe([]);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('CredentialsEnvelope', whatAStackSaysItHolds()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
