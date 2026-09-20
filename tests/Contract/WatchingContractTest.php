<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Shelves;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\WhatTheContractAccepts;

// The Watching contract, run against the adapter and against the fake.
//
// `G2`'s shape. The promise is narrow and the narrowness is the point: what a
// member may watch is the core's answer, so the only thing both sides owe is
// to hand it over as it arrived — and to keep an unread library apart from an
// empty one, which is the single distinction a screen cannot recover on its
// own.

/** The machine a member's shelf is read from. */
function theHouseAShelfBelongsTo(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('s', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
    );
}

/** The session the shelf is asked under. */
function theSessionAShelfIsAskedUnder(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** Whoever the shelf belongs to. */
function theMemberWhoseShelfItIs(): Whose
{
    return Whose::member('ada');
}

/**
 * One row of a shelf, as a stack sends it.
 *
 * @return array<string, mixed>
 */
function oneHoldingOnTheWire(string $id, string $title, string $medium = 'film', ?int $year = 1999): array
{
    $said = ['id' => $id, 'title' => $title, 'medium' => $medium];

    return $year === null ? $said : [...$said, 'year' => $year];
}

/**
 * What a stack sends about one member's shelf.
 *
 * @param  list<array<string, mixed>> $holdings
 * @param  list<string>               $findings
 * @return array<string, mixed>
 */
function whatAStackSendsAboutAShelf(array $holdings, bool $available = true, array $findings = []): array
{
    return [
        'api_version' => 1,
        'kind' => 'held',
        'data' => [
            'id' => 'the-loft',
            'member' => 'ada',
            'available' => $available,
            'findings' => $findings,
            'holdings' => $holdings,
        ],
    ];
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Closure(): Watching>
 */
function everyWayOfReadingAShelf(MockResponse $answered, Watching $fake): array
{
    return [
        'the fake' => static fn(): Watching => $fake,
        'the adapter' => static function () use ($answered): Watching {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Shelves(new PinnedClients());
        },
    ];
}

/** One answer carried out of an `either()` arm. */
final readonly class WhatAShelfCameAwayWith
{
    public function __construct(public string $said) {}
}

/** What a stack answered, as one string, whichever arm it took. */
function whatAShelfSaid(Watching $watching): string
{
    return $watching->theShelfOf(
        theHouseAShelfBelongsTo(),
        theSessionAShelfIsAskedUnder(),
        theMemberWhoseShelfItIs(),
    )->either(
        told: static function (Shelf $shelf): WhatAShelfCameAwayWith {
            $rows = [];

            foreach ($shelf as $holding) {
                $rows[] = sprintf(
                    '%s/%s/%s',
                    $holding->titled(),
                    $holding->medium()->value,
                    $holding->year()->either(
                        dated: static fn(int $year): WhatAShelfCameAwayWith
                            => new WhatAShelfCameAwayWith((string) $year),
                        unstated: static fn(): WhatAShelfCameAwayWith
                            => new WhatAShelfCameAwayWith('undated'),
                    )->said,
                );
            }

            return new WhatAShelfCameAwayWith(sprintf('told:%s', implode('|', $rows)));
        },
        outOfReach: static function (Sentences $said): WhatAShelfCameAwayWith {
            $lines = [];

            foreach ($said as $sentence) {
                $lines[] = $sentence->shown();
            }

            return new WhatAShelfCameAwayWith(sprintf('out-of-reach:%s', implode('|', $lines)));
        },
        refused: static fn(Obstacle $why): WhatAShelfCameAwayWith
            => new WhatAShelfCameAwayWith(sprintf('refused:%s', $why->value)),
    )->said;
}

it('N3-R14 — hands over the shelf the core listed, unchanged', function (): void {
    // Unchanged is the assertion, and it is the whole requirement: what a
    // member may watch was decided by their entitlements and their age limit
    // before this was called, so neither side may drop a row, add one or
    // reorder them.
    $answered = MockResponse::make((string) json_encode(whatAStackSendsAboutAShelf([
        oneHoldingOnTheWire('a1', 'A film', 'film', 1999),
        oneHoldingOnTheWire('b2', 'A series', 'series', null),
    ])));

    $fake = AShelfThatWasRead::holding(Shelf::of(
        Holding::of(HoldingId::called('a1'), 'A film', Medium::Film, WhenItCameOut::in(1999)),
        Holding::of(HoldingId::called('b2'), 'A series', Medium::Series, WhenItCameOut::unstated()),
    ));

    foreach (everyWayOfReadingAShelf($answered, $fake) as $which => $build) {
        expect(whatAShelfSaid($build()))->toBe('told:A film/film/1999|A series/series/undated', $which);
    }
});

it('N3-R15 — tells a library that could not be read from a shelf with nothing on it', function (): void {
    // The distinction a screen cannot recover on its own. Both arrive as no
    // rows, and they say opposite things to the person reading: one is *you
    // have nothing here* and the other is *your collection is out of reach*.
    $unread = MockResponse::make((string) json_encode(
        whatAStackSendsAboutAShelf([], available: false, findings: ['The media server did not answer.']),
    ));

    foreach (everyWayOfReadingAShelf(
        $unread,
        AShelfThatWasRead::outOfReach(Sentences::of(Sentence::of('The media server did not answer.'))),
    ) as $which => $build) {
        expect(whatAShelfSaid($build()))->toBe('out-of-reach:The media server did not answer.', $which);
    }

    $empty = MockResponse::make((string) json_encode(whatAStackSendsAboutAShelf([])));

    foreach (everyWayOfReadingAShelf($empty, AShelfThatWasRead::holdingNothing()) as $which => $build) {
        expect(whatAShelfSaid($build()))->toBe('told:', $which);
    }
});

it('N1-R10 — says the same about a reading it could not get', function (): void {
    $refusals = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
        // A shelf whose rows this side cannot read is the same thing to the
        // member as one that never arrived.
        [
            MockResponse::make((string) json_encode(whatAStackSendsAboutAShelf([
                ['id' => 'a1', 'title' => 'A film', 'medium' => 'hologram'],
            ]))),
            Obstacle::StackDidNotAnswer,
        ],
        // A blank line among real ones. One type decides what an empty
        // sentence means and this one decides what an unreadable answer means
        // to whoever is looking at it — a stack that sent a blank sentence
        // sent something this app cannot show, which is the same to a member
        // as an answer that never arrived.
        [
            MockResponse::make((string) json_encode(
                whatAStackSendsAboutAShelf([], available: false, findings: ['   ']),
            )),
            Obstacle::StackDidNotAnswer,
        ],
    ];

    foreach ($refusals as [$answered, $why]) {
        foreach (everyWayOfReadingAShelf($answered, AShelfThatWasRead::met($why)) as $which => $build) {
            expect(whatAShelfSaid($build()))->toBe(sprintf('refused:%s', $why->value), $which);
        }
    }
});

it('N3-R14 — the operator has no shelf, and that is an answer', function (): void {
    // Read *as* an account, and the operator is not one. Answered rather than
    // asked for: a request naming nobody is a request the stack would refuse,
    // and refusing it here spares the round trip and says the true thing.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSendsAboutAShelf([])))]);

    $answer = new Shelves(new PinnedClients())->theShelfOf(
        theHouseAShelfBelongsTo(),
        theSessionAShelfIsAskedUnder(),
        Whose::theOperator(),
    );

    expect($answer->either(
        told: static fn(): WhatAShelfCameAwayWith => new WhatAShelfCameAwayWith('told'),
        outOfReach: static fn(): WhatAShelfCameAwayWith
            => new WhatAShelfCameAwayWith('out-of-reach'),
        refused: static fn(Obstacle $why): WhatAShelfCameAwayWith
            => new WhatAShelfCameAwayWith($why->value),
    )->said)->toBe(Obstacle::NotForThisAccount->value);
});

it('G12 — the payload this suite stands a shelf in with is one a stack would send', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('HeldEnvelope', whatAStackSendsAboutAShelf([
        oneHoldingOnTheWire('a1', 'A film', 'film', 1999),
        oneHoldingOnTheWire('b2', 'A series', 'series', null),
        oneHoldingOnTheWire('c3', 'Something else', 'other', 2012),
    ])))->toBe([], "The payload this suite stands in for a shelf with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout(
            'HeldEnvelope',
            whatAStackSendsAboutAShelf([], available: false, findings: ['The media server did not answer.']),
        ))->toBe([], "The unreadable-shelf payload this suite stands in with is not one a stack would send.\n");
});
