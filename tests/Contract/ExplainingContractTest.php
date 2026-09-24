<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheGlossary;
use Modules\Sdk\Api\Explainers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatExplainsItsWords;
use Tests\Support\WhatTheContractAccepts;

// The Explaining contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `SelfCheckingContractTest`'s argument one endpoint along.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack asked for its words. */
function aStackThatExplainsItself(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with. */
function theSameWords(): TheGlossary
{
    return TheGlossary::of(
        AWord::explained('pin', 'The version a service is held at', 'A service runs the version it is pinned to until an update moves the pin.'),
        AWord::explained('seeding', 'Sharing a finished download', '', 'sharing', 'uploading'),
    );
}

/**
 * The payload a stack sends for that.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysItsWordsMean(string $seedingMeans = 'Sharing a finished download'): array
{
    return [
        'api_version' => 1,
        'kind' => 'glossary',
        'data' => [
            'words' => [
                [
                    'word' => 'pin',
                    'short' => 'The version a service is held at',
                    'deep' => 'A service runs the version it is pinned to until an update moves the pin.',
                    'also_called' => [],
                ],
                ['word' => 'seeding', 'short' => $seedingMeans, 'also_called' => ['sharing', 'uploading']],
            ],
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Explaining>
 */
function everyWayOfAskingForTheWords(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Explaining => $why instanceof Obstacle
            ? AStackThatExplainsItsWords::met($why)
            : AStackThatExplainsItsWords::with(theSameWords()),
        'the adapter' => static function () use ($answered): Explaining {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Explainers(new PinnedClients());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheWordsTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything the reading says, folded to one line, so two answers can be compared. */
function everythingTheWordsSay(Explaining $explaining): string
{
    return $explaining->glossaryOn(aStackThatExplainsItself(), Session::of('a-session-not-a-secret'))->either(
        found: static function (TheGlossary $words): WhatTheWordsTurnedOutToSay {
            $said = [];

            foreach ($words as $word) {
                $said[] = sprintf('%s|%s|%s|%s', $word->word(), $word->short(), $word->deep(), implode(',', [...$word->alsoCalled()]));
            }

            return new WhatTheWordsTurnedOutToSay(implode(' / ', $said));
        },
        met: static fn(Obstacle $why): WhatTheWordsTurnedOutToSay => new WhatTheWordsTurnedOutToSay($why->value),
    )->said;
}

it('comes away with every word, both glosses and every other name, in order', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysItsWordsMean()));

    foreach (everyWayOfAskingForTheWords($answered) as $which => $make) {
        expect(everythingTheWordsSay($make()))->toBe(
            'pin|The version a service is held at|A service runs the version it is pinned to until an update moves the pin.| / seeding|Sharing a finished download||sharing,uploading',
            $which,
        );
    }
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingForTheWords($answered, $why) as $which => $make) {
            expect(everythingTheWordsSay($make()))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('a word this app cannot read is an obstacle, never a word half-explained', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItsWordsMean(seedingMeans: ' ')))]);

    expect(everythingTheWordsSay(new Explainers(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('asks the explain endpoint naming no word, which is how the whole glossary is asked for', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysItsWordsMean()))]);

    everythingTheWordsSay(new Explainers(new PinnedClients()));

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toContain('/api/explain')
        ->and($sent?->query()->all())->toBe([]);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('GlossaryEnvelope', whatAStackSaysItsWordsMean()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
