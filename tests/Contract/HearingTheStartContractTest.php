<?php

declare(strict_types=1);

use Modules\Dx\Internal\WhatAStackWouldSay;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HearingTheStart;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatAStartWaitsOn;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\StartLines;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatSaysWhatItWaitsOn;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;
use Tests\Support\WhereEachOpenResumed;

// The HearingTheStart contract, run against the adapter and against the fake.
//
// Every screen test that follows a start will hand its subject an
// `AStackThatSaysWhatItWaitsOn` and never open a stream, so a fake that
// answered in a way the adapter never does would let a screen pass against a
// stack that cannot exist.
//
// The adapter is given a stream that says its piece and ends, because that is
// the only kind a mocked response can be. What it answers across the wakes is
// what the fake is scripted to answer, and every assertion is about those
// answers rather than about how either produced them.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** A stack a start runs on, reachable and pinned so the adapter will build a client. */
function aStackAStartRunsOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('s', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.43:8443'),
        Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS)),
    );
}

/** The session the start is followed with. */
function theSessionAStartIsFollowedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** A dashboard said between two start lines, which says nothing about the start. */
function aDashboardWhileItStarts(): string
{
    return anEventWhileItStarts('dashboard', json_encode(['api_version' => 1, 'kind' => 'dashboard', 'data' => WhatAStackWouldSay::inside('DashboardEnvelope')], JSON_THROW_ON_ERROR));
}

/** One event on the stream, as the core frames it. */
function anEventWhileItStarts(string $kind, string $data): string
{
    return sprintf("id: run-1\nevent: %s\ndata: %s\n\n", $kind, $data);
}

/** One start line as the core frames it, carrying whatever payload a test names. */
function aStartLine(mixed $said): string
{
    return anEventWhileItStarts('start', json_encode(['api_version' => 1, 'kind' => 'start', 'data' => $said], JSON_THROW_ON_ERROR));
}

/**
 * Both ways of hearing a start, each set up to hear the same thing.
 *
 * @param  list<MockResponse>  $streams  what the far end answers each time the adapter opens the stream
 * @param  list<WhatAStartWaitsOn>  $script  what the fake is scripted to answer, wake by wake
 * @return array<string, Closure(): HearingTheStart>
 */
function everyWayOfHearingAStart(array $streams, array $script): array
{
    return [
        'the fake' => static fn(): HearingTheStart => AStackThatSaysWhatItWaitsOn::saying(...$script),
        'the adapter' => static function () use ($streams): HearingTheStart {
            MockClient::destroyGlobal();
            MockClient::global($streams);

            return new StartLines(new PinnedClients());
        },
    ];
}

/**
 * What one way of hearing a start answers across as many wakes as asked.
 *
 * @return list<string>
 */
function whatWakesHearOfAStart(HearingTheStart $hearing, int $wakes): array
{
    $heard = [];

    for ($wake = 0; $wake < $wakes; $wake++) {
        $heard[] = $hearing->whatItWaitsOn(aStackAStartRunsOn(), theSessionAStartIsFollowedWith())->either(
            saying: static fn(string $line): TheWordCarriedOut => new TheWordCarriedOut(sprintf('saying "%s"', $line)),
            nothingNew: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing new'),
            met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
        )->said;
    }

    return $heard;
}

it('hears the newest of what a start is waiting for, and then nothing new', function (): void {
    foreach (everyWayOfHearingAStart(
        [
            MockResponse::make(sprintf('%s%s%s', aStartLine('Waiting for the database'), aDashboardWhileItStarts(), aStartLine('Waiting for sonarr to answer'))),
            MockResponse::make(''),
        ],
        [WhatAStartWaitsOn::saying('Waiting for sonarr to answer')],
    ) as $which => $make) {
        expect(whatWakesHearOfAStart($make(), 2))->toBe(['saying "Waiting for sonarr to answer"', 'nothing new'], $which);
    }
});

it('hears nothing new from a stream that carried no start line', function (): void {
    foreach (everyWayOfHearingAStart(
        [MockResponse::make(aDashboardWhileItStarts())],
        [],
    ) as $which => $make) {
        expect(whatWakesHearOfAStart($make(), 1))->toBe(['nothing new'], $which);
    }
});

it('cannot hear a start line that is blank or not text, and says so as an answer it could not read', function (): void {
    foreach ([aStartLine('   '), aStartLine(['waiting' => 'for sonarr']), anEventWhileItStarts('start', 'not json at all')] as $said) {
        foreach (everyWayOfHearingAStart(
            [MockResponse::make($said)],
            [WhatAStartWaitsOn::met(Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead))],
        ) as $which => $make) {
            expect(whatWakesHearOfAStart($make(), 1))->toBe([KindOfObstacle::AnswerCouldNotBeRead->value], $which);
        }
    }
});

it('tells a session the stack refused from a stack that could not be heard, while a start runs', function (): void {
    foreach ([
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ] as [$answered, $why]) {
        foreach (everyWayOfHearingAStart([$answered], [WhatAStartWaitsOn::met($why)]) as $which => $make) {
            expect(whatWakesHearOfAStart($make(), 1))->toBe([$why->kind()->value], $which);
        }
    }
});

/**
 * What a stream still open after the given lines sends, up to the read that
 * finds nothing.
 *
 * The SDK reads a stream 8192 bytes at a time and only learns it ended on the
 * read after the last byte. A body of exactly one read ends in a read that
 * comes back empty, which is what a stream that is still open looks like
 * between two lines. The padding is an event-stream comment, which carries
 * nothing.
 */
function aStartStreamStillOpenAfter(string $said): string
{
    return sprintf("%s:%s\n", $said, str_repeat(' ', 8192 - strlen($said) - 2));
}

it('opens a start stream once while it runs, and takes what arrived after that without asking again', function (): void {
    // Only the adapter can be asked this. A wake that reopened the stream to
    // read it would be a request to the stack every time the screen settles.
    MockClient::destroyGlobal();
    $stack = MockClient::global([MockResponse::make(aStartStreamStillOpenAfter(aStartLine('Waiting for the database'))), MockResponse::make('')]);

    expect(whatWakesHearOfAStart(new StartLines(new PinnedClients()), 2))
        ->toBe(['saying "Waiting for the database"', 'nothing new']);

    $stack->assertSentCount(1);
});

it('opens a start stream that ended again on the next ask', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aStartStreamStillOpenAfter(aStartLine('Waiting for the database'))),
        MockResponse::make(aStartLine('Waiting for sonarr to answer')),
    ]);

    expect(whatWakesHearOfAStart(new StartLines(new PinnedClients()), 3))
        ->toBe(['saying "Waiting for the database"', 'nothing new', 'saying "Waiting for sonarr to answer"'])
        ->and(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1']);
});

it('holds no start stream after a line it could not read, so the next ask opens again', function (): void {
    // The stream is still open when the unreadable line arrives, so nothing
    // but the refusal itself lets go of it.
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aStartStreamStillOpenAfter(aStartLine('   '))),
        MockResponse::make(aStartLine('Waiting for sonarr to answer')),
    ]);

    expect(whatWakesHearOfAStart(new StartLines(new PinnedClients()), 2))
        ->toBe([KindOfObstacle::AnswerCouldNotBeRead->value, 'saying "Waiting for sonarr to answer"'])
        ->and(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1']);
});

it('forgets where the start stream left off once let go of', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aStartLine('Waiting for the database')),
        MockResponse::make(''),
    ]);
    $hearing = new StartLines(new PinnedClients());

    whatWakesHearOfAStart($hearing, 1);
    $hearing->letGo();
    whatWakesHearOfAStart($hearing, 1);

    expect(WhereEachOpenResumed::in($stack))->toBe([null, null]);
});

it('hears nothing new once let go of', function (): void {
    foreach (everyWayOfHearingAStart([], []) as $which => $make) {
        expect($make()->letGo()->either(
            saying: static fn(string $line): TheWordCarriedOut => new TheWordCarriedOut($line),
            nothingNew: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing new'),
            met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
        )->said)->toBe('nothing new', $which);
    }
});

it('stands in for a stack with start lines the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StartEnvelope', ['api_version' => 1, 'kind' => 'start', 'data' => 'Waiting for the database']))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
