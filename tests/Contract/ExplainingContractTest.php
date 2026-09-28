<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheGlossary;
use Modules\Kernel\Api\WhatElseItIsCalled;
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
                    'forms' => ['pinned'],
                ],
                ['word' => 'seeding', 'short' => $seedingMeans, 'also_called' => ['sharing', 'uploading'], 'forms' => []],
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
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('WordEnvelope', whatAStackSaysOfOneWord()))
        ->toBe([], "The payload this suite stands in for a stack asked for one word with is not one a stack would send.\n");
});

/**
 * The payload a stack sends for the one word `grabbed` is asked for by.
 *
 * @return array<string, mixed>
 */
function whatAStackSaysOfOneWord(): array
{
    return [
        'api_version' => 1,
        'kind' => 'word',
        'data' => [
            'word' => 'grab',
            'short' => 'Sending a release to the download client',
            'deep' => null,
            'also_called' => ['snatch'],
            'forms' => ['grabbed'],
        ],
    ];
}

/**
 * Both ways of asking for one word, each set up to produce the same answer.
 *
 * @return array<string, Closure(): Explaining>
 */
function everyWayOfAskingForOneWord(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Explaining => $why instanceof Obstacle
            ? AStackThatExplainsItsWords::met($why)
            : AStackThatExplainsItsWords::with(theSameWords())->alsoExplaining(TheGlossary::of(
                AWord::explained('grab', 'Sending a release to the download client', '', 'snatch')
                    ->writtenAs(WhatElseItIsCalled::formsOf('grabbed')),
            )),
        'the adapter' => static function () use ($answered): Explaining {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Explainers(new PinnedClients());
        },
    ];
}

/** Everything asking for one word says, folded to one line. */
function everythingOneWordSays(Explaining $explaining, string $word): string
{
    return $explaining->wordOn(aStackThatExplainsItself(), Session::of('a-session-not-a-secret'), AWordInUse::named($word))->either(
        explained: static fn(AWord $entry): WhatTheWordsTurnedOutToSay => new WhatTheWordsTurnedOutToSay(sprintf(
            '%s|%s|%s|%s|%s',
            $entry->word(),
            $entry->short(),
            $entry->deep(),
            implode(',', [...$entry->alsoCalled()]),
            $entry->explains(AWordInUse::named($word)) ? 'explains it' : 'explains something else',
        )),
        unexplained: static fn(): WhatTheWordsTurnedOutToSay => new WhatTheWordsTurnedOutToSay('no entry'),
        met: static fn(Obstacle $why): WhatTheWordsTurnedOutToSay => new WhatTheWordsTurnedOutToSay($why->value),
    )->said;
}

it('comes away with the entry for one word, found by a form the stack writes it in', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSaysOfOneWord()));

    foreach (everyWayOfAskingForOneWord($answered) as $which => $make) {
        expect(everythingOneWordSays($make(), 'grabbed'))->toBe('grab|Sending a release to the download client||snatch|explains it', $which);
    }
});

it('a word the stack does not explain is its answer, never an obstacle', function (): void {
    $answered = MockResponse::make('{"api_version":1,"kind":"error","data":{"error":"No word goes by that name."}}', 404);

    foreach (everyWayOfAskingForOneWord($answered) as $which => $make) {
        expect(everythingOneWordSays($make(), 'nothing-by-that-name'))->toBe('no entry', $which);
    }
});

it('asking for one word tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingForOneWord($answered, $why) as $which => $make) {
            expect(everythingOneWordSays($make(), 'grabbed'))->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('a word asked for that this app cannot read is an obstacle, never a word half-explained', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode([...whatAStackSaysOfOneWord(), 'data' => ['word' => 'grab']]))]);

    expect(everythingOneWordSays(new Explainers(new PinnedClients()), 'grabbed'))->toBe(Obstacle::StackDidNotAnswer->value);
});

it('asks the explain endpoint naming the one word, as it was drawn', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfOneWord()))]);

    everythingOneWordSays(new Explainers(new PinnedClients()), 'grabbed');

    $sent = $mock->getLastPendingRequest();

    expect($sent?->getUrl())->toContain('/api/explain')
        ->and($sent?->query()->all())->toBe(['word' => 'grabbed']);
});

it('the fake remembers every word it was asked for alone', function (): void {
    $fake = AStackThatExplainsItsWords::with(theSameWords());

    everythingOneWordSays($fake, 'grabbed');
    everythingOneWordSays($fake, 'pin');

    expect($fake->wordsAsked())->toBe(['grabbed', 'pin'])
        ->and($fake->askedAbout()?->name()->shown())->toBe('The loft');
});
