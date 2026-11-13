<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\FindingTitles;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowTheKindsWent;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\UnitOfTitle;
use Modules\Kernel\Api\WhatASearchFound;
use Modules\Kernel\Api\WhatCanBeAskedFor;
use Modules\Kernel\Api\WhatToLookFor;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Kernel\Api\WhereTheNextPageBegins;
use Modules\Kernel\Api\WhichTitles;
use Modules\Sdk\Api\Finders;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Tests\Support\Fakes\AStackThatFindsTitles;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The house a member searches. */
function aHouseToSearch(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('s', Nonce::SHORTEST))),
        StackName::of('Home'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

/**
 * What the core answers a search with, changed where a case says.
 *
 * @param  array<mixed>       $changed the page's own fields that differ
 * @param  array<mixed>|null  $titles  the titles, where they differ
 * @return array<string, mixed>
 */
function whatTheCoreFound(array $changed = [], ?array $titles = null): array
{
    return ['api_version' => 1, 'kind' => 'askable', 'data' => [
        'member' => 'sanne',
        'term' => 'dune',
        'kinds' => ['movies', 'tv'],
        'approval' => 'What you ask for arrives without waiting for a yes.',
        'asking' => ['films' => ['limit' => 4, 'remaining' => 2, 'used' => 2], 'television' => ['limit' => 1, 'remaining' => 1, 'used' => 0], 'policy' => 'trusted', 'standing' => 'within-quota'],
        'next' => 'page-2',
        'titles' => $titles ?? [
            ['id' => '438631', 'kind' => 'movies', 'title' => 'Dune', 'year' => 2021, 'stands' => 'askable'],
            ['id' => '693134', 'kind' => 'movies', 'title' => 'Dune: Part Two', 'year' => null, 'stands' => 'on-its-way'],
            ['id' => '90228', 'kind' => 'tv', 'title' => 'Dune: Prophecy', 'stands' => 'partly-here'],
        ],
        ...$changed,
    ]];
}

/** The page that payload stands for, as the fake is handed it. */
function theSamePageFound(): WhatASearchFound
{
    return WhatASearchFound::before(
        WhereTheNextPageBegins::at('page-2'),
        'What you ask for arrives without waiting for a yes.',
        AFoundTitle::listed('438631', KindOfTitle::Film, 'Dune', WhenItCameOut::in(2021), WhereAFoundTitleStands::Askable),
        AFoundTitle::listed('693134', KindOfTitle::Film, 'Dune: Part Two', WhenItCameOut::unstated(), WhereAFoundTitleStands::OnItsWay),
        AFoundTitle::listed('90228', KindOfTitle::Series, 'Dune: Prophecy', WhenItCameOut::unstated(), WhereAFoundTitleStands::PartlyHere),
    );
}

/** The adapter, answered with these responses in order. */
function aFinderAnswered(MockResponse ...$answered): FindingTitles
{
    MockClient::destroyGlobal();
    MockClient::global($answered);

    return new Finders(new PinnedClients());
}

/**
 * Both ways of searching, each set up to say the same.
 *
 * @return array<string, Closure(): FindingTitles>
 */
function everyWayOfSearching(MockResponse $answered, WhatASearchFound $page): array
{
    return [
        'the fake' => static fn(): FindingTitles => AStackThatFindsTitles::finding($page),
        'the adapter' => static fn(): FindingTitles => aFinderAnswered($answered),
    ];
}

/** A page, every part folded to one line. */
function whatTheSearchSaid(WhatASearchFound $page): string
{
    $titles = [];

    foreach ($page as $title) {
        $titles[] = sprintf(
            '%s:%s:%s:%s:%s',
            $title->id(),
            $title->kind()->value,
            $title->title(),
            $title->year()->either(dated: static fn(int $year): TheWordCarriedOut => new TheWordCarriedOut((string) $year), unstated: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'))->said,
            $title->stands()->value,
        );
    }

    $next = $page->followedBy(more: static fn(WhereTheNextPageBegins $next): TheWordCarriedOut => new TheWordCarriedOut($next->said()), last: static fn(): TheWordCarriedOut => new TheWordCarriedOut('last'))->said;

    return sprintf('%s|%s|%s', $page->approval(), $next, implode(',', $titles));
}

/** What searching came to, as a line. */
function howTheSearchWent(FindingTitles $finding, ?WhereTheNextPageBegins $after = null): string
{
    $words = WhatToLookFor::typed('  dune ');
    $went = $after instanceof WhereTheNextPageBegins
        ? $finding->pageAfter(aHouseToSearch(), Session::of('a-session-not-a-secret'), $words, $after)
        : $finding->firstPage(aHouseToSearch(), Session::of('a-session-not-a-secret'), $words);

    return $went->either(
        found: static fn(WhatASearchFound $page): TheWordCarriedOut => new TheWordCarriedOut(whatTheSearchSaid($page)),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('reads every part of a page either way: each title, the core\'s sentence on approval, and where the next page begins', function (): void {
    foreach (everyWayOfSearching(MockResponse::make((string) json_encode(whatTheCoreFound())), theSamePageFound()) as $which => $build) {
        expect(howTheSearchWent($build()))
            ->toBe('What you ask for arrives without waiting for a yes.|page-2|438631:movies:Dune:2021:askable,693134:movies:Dune: Part Two:-:on-its-way,90228:tv:Dune: Prophecy:-:partly-here', $which);
    }
});

it('reads a page with no next as the last one, and one with no titles as an answer', function (): void {
    expect(howTheSearchWent(aFinderAnswered(MockResponse::make((string) json_encode(whatTheCoreFound(['next' => null], []))))))
        ->toBe('What you ask for arrives without waiting for a yes.|last|');
});

it('asks for the words trimmed, naming nobody, and a later page from the place the last one named', function (): void {
    $finders = aFinderAnswered(MockResponse::make((string) json_encode(whatTheCoreFound())), MockResponse::make((string) json_encode(whatTheCoreFound())));

    howTheSearchWent($finders);
    howTheSearchWent($finders, WhereTheNextPageBegins::at('page-2'));

    $sent = array_map(
        static fn(Response $recorded): string => sprintf('%s?%s', $recorded->getPendingRequest()->getRequest()->resolveEndpoint(), http_build_query($recorded->getPendingRequest()->query()->all())),
        MockClient::global()->getRecordedResponses(),
    );

    expect($sent)->toBe(['/api/askable?term=dune', '/api/askable?term=dune&after=page-2']);
});

it('comes away with the obstacle where the core would not search', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"words"}', 400), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all'), Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead)],
    ];

    foreach ($table as [$answered, $why]) {
        expect(howTheSearchWent(aFinderAnswered($answered)))->toBe($why->kind()->name)
            ->and(howTheSearchWent(AStackThatFindsTitles::met($why)))->toBe($why->kind()->name);
    }
});

it('a page this app cannot read is an answer it could not read, never a shorter one', function (array $changed, ?array $titles): void {
    expect(howTheSearchWent(aFinderAnswered(MockResponse::make((string) json_encode(whatTheCoreFound($changed, $titles))))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'no sentence on approval' => [['approval' => null], null],
    'a next that is not words' => [['next' => 2], null],
    'a blank next' => [['next' => ' '], null],
    'titles that are not a list' => [['titles' => 'dune'], null],
    'a title that is not one' => [[], ['dune']],
    'a title with no id' => [[], [['kind' => 'movies', 'title' => 'Dune', 'stands' => 'askable']]],
    'a title with a blank id' => [[], [['id' => ' ', 'kind' => 'movies', 'title' => 'Dune', 'stands' => 'askable']]],
    'a title with a blank name' => [[], [['id' => '1', 'kind' => 'movies', 'title' => ' ', 'stands' => 'askable']]],
    'a kind this app has no word for' => [[], [['id' => '1', 'kind' => 'games', 'title' => 'Dune', 'stands' => 'askable']]],
    'a standing this app has no word for' => [[], [['id' => '1', 'kind' => 'movies', 'title' => 'Dune', 'stands' => 'gone']]],
    'a year that is not a number' => [[], [['id' => '1', 'kind' => 'movies', 'title' => 'Dune', 'year' => '2021', 'stands' => 'askable']]],
]);

it('an answer that is not a page at all is an answer it could not read', function (): void {
    expect(howTheSearchWent(aFinderAnswered(MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'askable', 'data' => 'nothing'])))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('AskableEnvelope', whatTheCoreFound()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});

/** A page of music the core found, with a title naming its maker and one naming nobody. */
function theMusicTheCoreFound(): string
{
    return (string) json_encode(whatTheCoreFound(['next' => null], [
        ['id' => 'mb-1', 'kind' => 'music', 'unit' => 'album', 'title' => 'Rumours', 'by' => 'Fleetwood Mac', 'year' => 1977, 'stands' => 'askable', 'cover' => 'https://covers.example/rumours.jpg'],
        ['id' => 'mb-2', 'kind' => 'music', 'unit' => 'artist', 'title' => 'Fleetwood Mac', 'by' => null, 'stands' => 'on-its-way'],
    ]));
}

/** Each title on the page, as what it was found among and who made it. */
function whoMadeWhatWasFound(FindingTitles $finding, WhatToLookFor $words): string
{
    return $finding->firstPage(aHouseToSearch(), Session::of('a-session-not-a-secret'), $words)->either(
        found: static function (WhatASearchFound $page): TheWordCarriedOut {
            $titles = [];

            foreach ($page as $title) {
                $titles[] = sprintf('%s:%s:%s', $title->id(), $title->among()->word(), $title->by(named: static fn(string $by): TheWordCarriedOut => new TheWordCarriedOut($by), unnamed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('-'))->said);
            }

            return new TheWordCarriedOut(implode(',', $titles));
        },
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

/**
 * What the adapter asked, each request as its path and query.
 *
 * @return list<string>
 */
function whatTheFinderAsked(): array
{
    return array_values(array_map(
        static fn(Response $recorded): string => sprintf('%s?%s', $recorded->getPendingRequest()->getRequest()->resolveEndpoint(), http_build_query($recorded->getPendingRequest()->query()->all())),
        MockClient::global()->getRecordedResponses(),
    ));
}

it('reads a title of music or books with its unit and who made it, and asks with the kind and unit chosen', function (): void {
    $words = WhatToLookFor::typed('rumours')->among(WhichTitles::of(KindOfTitle::Music, UnitOfTitle::Album));
    $finders = aFinderAnswered(MockResponse::make(theMusicTheCoreFound()), MockResponse::make(theMusicTheCoreFound()));

    expect(whoMadeWhatWasFound($finders, $words))->toBe('mb-1:music/album:Fleetwood Mac,mb-2:music/artist:-');

    $finders->pageAfter(aHouseToSearch(), Session::of('a-session-not-a-secret'), $words, WhereTheNextPageBegins::at('page-2'));

    expect(whatTheFinderAsked())->toBe(['/api/askable?kind=music&unit=album&term=rumours', '/api/askable?kind=music&unit=album&term=rumours&after=page-2']);
});

it('a unit this app has no word for, or a maker that is not words, is an answer it could not read', function (array $title): void {
    expect(howTheSearchWent(aFinderAnswered(MockResponse::make((string) json_encode(whatTheCoreFound([], [$title]))))))
        ->toBe(KindOfObstacle::AnswerCouldNotBeRead->name);
})->with([
    'a unit this app has no word for' => [['id' => '1', 'kind' => 'music', 'unit' => 'single', 'title' => 'Dreams', 'stands' => 'askable']],
    'a unit that is not a word' => [['id' => '1', 'kind' => 'music', 'unit' => 3, 'title' => 'Dreams', 'stands' => 'askable']],
    'a maker that is not words' => [['id' => '1', 'kind' => 'music', 'unit' => 'album', 'title' => 'Dreams', 'by' => 7, 'stands' => 'askable']],
]);

/**
 * What the core lists a member as able to ask for, changed where a case says.
 *
 * @param  list<mixed>|string|null  $kinds
 * @return array<string, mixed>
 */
function whatTheCoreListed(array|string|null $kinds = null): array
{
    return ['api_version' => 1, 'kind' => 'askable-kinds', 'data' => [
        'member' => 'sanne',
        'kinds' => $kinds ?? [
            ['kind' => 'tv', 'units' => []],
            ['kind' => 'movies', 'units' => []],
            ['kind' => 'music', 'units' => ['album', 'artist']],
            ['kind' => 'books', 'units' => ['book', 'author', 'series']],
        ],
    ]];
}

/** What asking what can be asked for came to, as the words each choice is chosen by, or the obstacle. */
function whatCouldBeAskedFor(FindingTitles $finding): string
{
    return $finding->whatCanBeAskedFor(aHouseToSearch(), Session::of('a-session-not-a-secret'))->either(
        listed: static fn(WhatCanBeAskedFor $listed): TheWordCarriedOut => new TheWordCarriedOut(implode(',', array_map(
            static fn(WhichTitles $kind): string => implode('+', array_map(static fn(WhichTitles $unit): string => $unit->word(), [$kind, ...$listed->unitsBeside($kind)])),
            $listed->kinds(),
        ))),
        refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('reads what a member can ask for in the core\'s order, either way, asking for it by no name', function (): void {
    $listed = WhatCanBeAskedFor::listing()
        ->and(KindOfTitle::Series)
        ->and(KindOfTitle::Film)
        ->and(KindOfTitle::Music, UnitOfTitle::Album, UnitOfTitle::Artist)
        ->and(KindOfTitle::Books, UnitOfTitle::Book, UnitOfTitle::Author, UnitOfTitle::Series);
    $either = [
        'the fake' => AStackThatFindsTitles::finding()->listing(HowTheKindsWent::listed($listed)),
        'the adapter' => aFinderAnswered(MockResponse::make((string) json_encode(whatTheCoreListed()))),
    ];

    foreach ($either as $which => $finding) {
        expect(whatCouldBeAskedFor($finding))->toBe('films-and-series,music/album+music/album+music/artist,books/book+books/book+books/author+books/series', $which);
    }

    expect(whatTheFinderAsked())->toBe(['/api/askable/kinds?']);
});

it('comes away with the obstacle where the core would not list what can be asked for, or listed what this app cannot read', function (MockResponse $answered, KindOfObstacle $kind): void {
    expect(whatCouldBeAskedFor(aFinderAnswered($answered)))->toBe($kind->name);
})->with([
    'refused' => [MockResponse::make('{"error":"no"}', 401), KindOfObstacle::CredentialWasRefused],
    'not json at all' => [MockResponse::make('not json at all'), KindOfObstacle::AnswerCouldNotBeRead],
    'no table at all' => [MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'askable-kinds', 'data' => 'nothing'])), KindOfObstacle::AnswerCouldNotBeRead],
    'kinds that are not a list' => [MockResponse::make((string) json_encode(whatTheCoreListed('music'))), KindOfObstacle::AnswerCouldNotBeRead],
    'a kind that is not one' => [MockResponse::make((string) json_encode(whatTheCoreListed(['music']))), KindOfObstacle::AnswerCouldNotBeRead],
    'a kind this app has no word for' => [MockResponse::make((string) json_encode(whatTheCoreListed([['kind' => 'games', 'units' => []]]))), KindOfObstacle::AnswerCouldNotBeRead],
    'a kind with no units' => [MockResponse::make((string) json_encode(whatTheCoreListed([['kind' => 'music']]))), KindOfObstacle::AnswerCouldNotBeRead],
    'a unit this app has no word for' => [MockResponse::make((string) json_encode(whatTheCoreListed([['kind' => 'music', 'units' => ['single']]]))), KindOfObstacle::AnswerCouldNotBeRead],
    'a unit that is not a word' => [MockResponse::make((string) json_encode(whatTheCoreListed([['kind' => 'music', 'units' => [1]]]))), KindOfObstacle::AnswerCouldNotBeRead],
]);
