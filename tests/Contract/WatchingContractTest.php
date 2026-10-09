<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnEpisode;
use Modules\Kernel\Api\ASeason;
use Modules\Kernel\Api\ATitle;
use Modules\Kernel\Api\Episodes;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Genres;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowLongItRuns;
use Modules\Kernel\Api\ItsDetails;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Location;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\NumberedAs;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Seasons;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\WhatTheTitleIs;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhenItWasReleased;
use Modules\Kernel\Api\WhereItPlays;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Shelves;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AShelfThatWasRead;
use Tests\Support\TheWordCarriedOut;
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
            'rehearsed' => false,
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

/** What a stack answered, as one string, whichever arm it took. */
function whatAShelfSaid(Watching $watching): string
{
    return $watching->theShelfOf(
        theHouseAShelfBelongsTo(),
        theSessionAShelfIsAskedUnder(),
        theMemberWhoseShelfItIs(),
    )->either(
        told: static function (Shelf $shelf): TheWordCarriedOut {
            $rows = [];

            foreach ($shelf as $holding) {
                $rows[] = sprintf(
                    '%s/%s/%s',
                    $holding->titled(),
                    $holding->medium()->value,
                    $holding->year()->either(
                        dated: static fn(int $year): TheWordCarriedOut
                            => new TheWordCarriedOut((string) $year),
                        unstated: static fn(): TheWordCarriedOut
                            => new TheWordCarriedOut('undated'),
                    )->said,
                );
            }

            return new TheWordCarriedOut(sprintf('told:%s', implode('|', $rows)));
        },
        outOfReach: static function (Sentences $said): TheWordCarriedOut {
            $lines = [];

            foreach ($said as $sentence) {
                $lines[] = $sentence->shown();
            }

            return new TheWordCarriedOut(sprintf('out-of-reach:%s', implode('|', $lines)));
        },
        refused: static fn(Obstacle $why): TheWordCarriedOut
            => new TheWordCarriedOut(sprintf('refused:%s', $why->kind()->value)),
    )->said;
}

it('hands over the shelf the core listed, unchanged', function (): void {
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

it('tells a library that could not be read from a shelf with nothing on it', function (): void {
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

it('tells a refused session from a stack that did not answer, where the shelf could not be got', function (): void {
    $refusals = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        // A shelf whose rows this side cannot read is the same thing to the
        // member as one that never arrived.
        [
            MockResponse::make((string) json_encode(whatAStackSendsAboutAShelf([
                ['id' => 'a1', 'title' => 'A film', 'medium' => 'hologram'],
            ]))),
            Obstacle::of(KindOfObstacle::StackDidNotAnswer),
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
            Obstacle::of(KindOfObstacle::StackDidNotAnswer),
        ],
    ];

    foreach ($refusals as [$answered, $why]) {
        foreach (everyWayOfReadingAShelf($answered, AShelfThatWasRead::met($why)) as $which => $build) {
            expect(whatAShelfSaid($build()))->toBe(sprintf('refused:%s', $why->kind()->value), $which);
        }
    }
});

it('the operator has no shelf, and that is an answer', function (): void {
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
        told: static fn(): TheWordCarriedOut => new TheWordCarriedOut('told'),
        outOfReach: static fn(): TheWordCarriedOut
            => new TheWordCarriedOut('out-of-reach'),
        refused: static fn(Obstacle $why): TheWordCarriedOut
            => new TheWordCarriedOut($why->kind()->value),
    )->said)->toEqual(KindOfObstacle::NotForThisAccount->value);
});

// G12 — a suite standing a payload in for a stack reads it against the contract
it('the payload this suite stands a shelf in with is one a stack would send', function (): void {
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

/** What a stack answered about the household's defaults, as one string, whichever arm it took. */
function whatTheDefaultShelfSaid(Watching $watching): string
{
    return $watching->theDefaultShelf(theHouseAShelfBelongsTo(), theSessionAShelfIsAskedUnder())->either(
        told: static fn(Shelf $shelf): TheWordCarriedOut => new TheWordCarriedOut(sprintf('told:%d', iterator_count($shelf->getIterator()))),
        outOfReach: static fn(): TheWordCarriedOut => new TheWordCarriedOut('out-of-reach'),
        refused: static fn(Obstacle $why): TheWordCarriedOut
            => new TheWordCarriedOut(sprintf('refused:%s', $why->kind()->value)),
    )->said;
}

it('hands over the shelf the household\'s defaults hold, unchanged', function (): void {
    // Nobody's shelf, and held to the same promise as a member's: what the
    // core listed is what arrives, and an unread library is not an empty one.
    $answered = MockResponse::make((string) json_encode(whatAStackSendsAboutAShelf([
        oneHoldingOnTheWire('a1', 'A film', 'film', 1999),
    ])));

    $fake = AShelfThatWasRead::holding(Shelf::of(
        Holding::of(HoldingId::called('a1'), 'A film', Medium::Film, WhenItCameOut::in(1999)),
    ));

    foreach (everyWayOfReadingAShelf($answered, $fake) as $which => $build) {
        expect(whatTheDefaultShelfSaid($build()))->toBe('told:1', $which);
    }

    $unread = MockResponse::make((string) json_encode(
        whatAStackSendsAboutAShelf([], available: false, findings: ['The media server did not answer.']),
    ));

    foreach (everyWayOfReadingAShelf(
        $unread,
        AShelfThatWasRead::outOfReach(Sentences::of(Sentence::of('The media server did not answer.'))),
    ) as $which => $build) {
        expect(whatTheDefaultShelfSaid($build()))->toBe('out-of-reach', $which);
    }

    foreach (everyWayOfReadingAShelf(
        MockResponse::make('{"error":"no"}', 401),
        AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::CredentialWasRefused)),
    ) as $which => $build) {
        expect(whatTheDefaultShelfSaid($build()))->toBe(sprintf('refused:%s', KindOfObstacle::CredentialWasRefused->value), $which);
    }
});

it('counts which of the two shelves the fake was asked for', function (): void {
    $watching = AShelfThatWasRead::holdingNothing();

    $watching->theDefaultShelf(theHouseAShelfBelongsTo(), theSessionAShelfIsAskedUnder());
    $watching->theShelfOf(theHouseAShelfBelongsTo(), theSessionAShelfIsAskedUnder(), theMemberWhoseShelfItIs());

    expect($watching->askingsForTheDefaults())->toBe(1)
        ->and($watching->askings())->toBe(2);
});

/**
 * What a stack sends about one title, with the title changed where a case says, or none where it is absent.
 *
 * @param  array<array-key, mixed>|null $title
 * @return array<string, mixed>
 */
function whatAStackSendsAboutATitle(?array $title): array
{
    return ['api_version' => 1, 'kind' => 'title', 'data' => ['id' => 'ada-id', 'member' => 'ada', 'rehearsed' => false, 'title' => $title]];
}

/**
 * A series, as a stack sends it: its door known, an episode located and one not.
 *
 * @return array<string, mixed>
 */
function aSeriesOnTheWire(): array
{
    $door = ['fingerprint' => str_repeat('d', 64)];

    return [
        'id' => 's1', 'title' => 'Slow Horses', 'medium' => 'series', 'year' => 2022,
        'overview' => 'Spies who failed.', 'minutes' => null, 'genres' => ['Thriller', 'Drama'],
        'certificate' => '16', 'released' => '2022-04-01', 'door' => $door,
        'seasons' => [[
            'id' => 'season-1', 'name' => 'Season 1', 'number' => 1,
            'episodes' => [
                ['id' => 'e1', 'title' => 'Failure\'s Contagious', 'medium' => 'episode', 'number' => 1, 'minutes' => 49,
                    'overview' => 'Lamb.', 'stream_from' => 'https://192.168.1.42:8920/Videos/e1/master.m3u8', 'door' => $door],
                ['id' => 'e2', 'title' => 'A special', 'medium' => 'episode', 'unlocated' => 'The front door has no certificate yet.'],
            ],
        ]],
    ];
}

/** What the core said about a title, as one line, whichever arm it took. */
function whatATitleSaid(Watching $watching, ?Whose $whose = null): string
{
    return $watching->theTitle(theHouseAShelfBelongsTo(), theSessionAShelfIsAskedUnder(), $whose ?? theMemberWhoseShelfItIs(), HoldingId::called('s1'))->either(
        told: static function (ATitle $title): TheWordCarriedOut {
            $said = [$title->holding()->titled(), $title->about(), implode('+', array_map(strval(...), [...$title->genres()])), $title->certificate()];
            $said[] = $title->released()->either(on: static fn(int $y, int $m, int $d): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%d-%d-%d', $y, $m, $d)), unstated: static fn(): TheWordCarriedOut => new TheWordCarriedOut('undated'))->said;

            foreach ($title->seasons() as $season) {
                foreach ($season->episodes() as $episode) {
                    $said[] = sprintf('%s/%s/%s', $season->named(), $episode->titled(), $episode->plays()->either(
                        at: static fn(Location $at): TheWordCarriedOut => new TheWordCarriedOut($at->forThePlayer()),
                        cannot: static fn(Sentence $why): TheWordCarriedOut => new TheWordCarriedOut($why->shown()),
                        doesNotStream: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
                    )->said);
                }
            }

            return new TheWordCarriedOut(implode('|', $said));
        },
        absent: static fn(): TheWordCarriedOut => new TheWordCarriedOut('absent'),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused:%s', $why->kind()->value)),
    )->said;
}

/** The series that payload stands for, as the fake is handed it. */
function theSameSeries(): ATitle
{
    $door = Fingerprint::of(str_repeat('d', 64));

    return ATitle::of(
        Holding::of(HoldingId::called('s1'), 'Slow Horses', Medium::Series, WhenItCameOut::in(2022)),
        ItsDetails::of('Spies who failed.', HowLongItRuns::unstated(), Genres::of('Thriller', 'Drama'), '16', WhenItWasReleased::on(2022, 4, 1)),
        WhereItPlays::doesNotStream(),
        Seasons::of(ASeason::of('Season 1', Episodes::of(
            AnEpisode::of(HoldingId::called('e1'), 'Failure\'s Contagious', NumberedAs::number(1), HowLongItRuns::minutes(49), 'Lamb.', WhereItPlays::at(Location::of('https://192.168.1.42:8920/Videos/e1/master.m3u8'), $door)),
            AnEpisode::of(HoldingId::called('e2'), 'A special', NumberedAs::none(), HowLongItRuns::unstated(), '', WhereItPlays::cannot(Sentence::of('The front door has no certificate yet.'))),
        ))),
    );
}

it('hands over one title in full, each episode with where it streams from or why not', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSendsAboutATitle(aSeriesOnTheWire())));

    foreach (everyWayOfReadingAShelf($answered, AShelfThatWasRead::holdingNothing()->answeringTheTitle(WhatTheTitleIs::told(theSameSeries()))) as $which => $build) {
        expect(whatATitleSaid($build()))->toBe(
            'Slow Horses|Spies who failed.|Thriller+Drama|16|2022-4-1|Season 1/Failure\'s Contagious/https://192.168.1.42:8920/Videos/e1/master.m3u8|Season 1/A special/The front door has no certificate yet.',
            $which,
        );
    }
});

it('answers a title the core did not hand over as absent', function (): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSendsAboutATitle(null)));

    foreach (everyWayOfReadingAShelf($answered, AShelfThatWasRead::holdingNothing()) as $which => $build) {
        expect(whatATitleSaid($build()))->toBe('absent', $which);
    }
});

it('says the stack did not answer where a title cannot be read', function (array $title): void {
    $answered = MockResponse::make((string) json_encode(whatAStackSendsAboutATitle($title)));

    foreach (everyWayOfReadingAShelf($answered, AShelfThatWasRead::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))) as $which => $build) {
        expect(whatATitleSaid($build()))->toBe(sprintf('refused:%s', KindOfObstacle::StackDidNotAnswer->value), $which);
    }
})->with([
    'a location with no door' => [[...aSeriesOnTheWire(), 'medium' => 'film', 'seasons' => [], 'door' => null, 'stream_from' => 'https://192.168.1.42:8920/Videos/s1/master.m3u8']],
    'a location that is not at the door' => [[...aSeriesOnTheWire(), 'medium' => 'film', 'seasons' => [], 'stream_from' => 'http://192.168.1.42:8096/Videos/s1']],
    'a door that is no fingerprint' => [[...aSeriesOnTheWire(), 'medium' => 'film', 'seasons' => [], 'door' => ['fingerprint' => 'abc'], 'stream_from' => 'https://192.168.1.42:8920/Videos/s1/master.m3u8']],
    'a release day the calendar does not have' => [[...aSeriesOnTheWire(), 'released' => '2022-02-30']],
    'a release that is no day' => [[...aSeriesOnTheWire(), 'released' => 'spring']],
    'a genre that is not words' => [[...aSeriesOnTheWire(), 'genres' => [7]]],
    'a medium this build does not know' => [[...aSeriesOnTheWire(), 'medium' => 'podcast']],
    'a season that is not one' => [[...aSeriesOnTheWire(), 'seasons' => ['season']]],
    'an episode that is not one' => [[...aSeriesOnTheWire(), 'seasons' => [['id' => 'x', 'name' => 'Season 1', 'episodes' => ['e']]]]],
    'a runtime that is not a number' => [[...aSeriesOnTheWire(), 'minutes' => 'long']],
]);

it('answers the operator, who has no shelf, as not theirs to ask', function (): void {
    expect(whatATitleSaid(new Shelves(new PinnedClients()), Whose::theOperator()))->toBe(sprintf('refused:%s', KindOfObstacle::NotForThisAccount->value));
});

it('asks for the title the shelf lists, as the member', function (): void {
    $asked = null;
    MockClient::destroyGlobal();
    MockClient::global(['*' => static function (PendingRequest $request) use (&$asked): MockResponse {
        $asked = [$request->getRequest()->resolveEndpoint(), $request->query()->all()];

        return MockResponse::make((string) json_encode(whatAStackSendsAboutATitle(null)));
    }]);

    whatATitleSaid(new Shelves(new PinnedClients()));

    expect($asked)->toBe(['/api/held/s1', ['member' => 'ada']]);
});

it('stands in for a stack with a title the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('TitleEnvelope', whatAStackSendsAboutATitle(aSeriesOnTheWire())))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('TitleEnvelope', whatAStackSendsAboutATitle(null)))->toBe([]);
});
