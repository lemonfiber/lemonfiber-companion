<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HearingTheWalk;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatTheWalkSaid;
use Modules\Sdk\Api\Narrators;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AStackThatNarrates;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;
use Tests\Support\WhereEachOpenResumed;

// The HearingTheWalk contract, run against the adapter and against the fake.
//
// Every screen test that follows a walk will hand its subject an
// `AStackThatNarrates` and never open a stream, so a fake that answered in a
// way the adapter never does would let a screen pass against a stack that
// cannot exist.
//
// The adapter is given a stream that says its piece and ends, because that is
// the only kind a mocked response can be. What it answers across the wakes is
// what the fake is scripted to answer, and every assertion is about those
// answers rather than about how either produced them.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** A stack a walk runs on, reachable and pinned so the adapter will build a client. */
function aStackAWalkRunsOn(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.43:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session the walk is followed with. */
function theSessionAWalkIsFollowedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * A step a walk says, as the stack sends it.
 *
 * @return array<string, mixed>
 */
function aStepTheWalkSays(string $step, string $said, string $detail): array
{
    return ['step' => $step, 'said' => $said, 'detail' => $detail];
}

/**
 * The step a walk says while it searches, with what was particular about it.
 *
 * @return array<string, mixed>
 */
function aSearchingStep(): array
{
    return aStepTheWalkSays('searching', 'Searching the indexers for Sintel', 'Two indexers answered');
}

/**
 * The step a walk says while it downloads, with nothing particular to add.
 *
 * @return array<string, mixed>
 */
function aDownloadingStep(): array
{
    return aStepTheWalkSays('downloading', 'Downloading Sintel', '');
}

/**
 * One step, as the envelope the stream carries it in.
 *
 * @param  array<string, mixed>  $step
 * @return array<string, mixed>
 */
function aStepEnvelope(array $step): array
{
    return ['api_version' => 1, 'kind' => 'step', 'data' => $step];
}

/** One event on the stream, as the core frames it. */
function anEventOnTheWalk(string $kind, string $data): string
{
    return sprintf("id: run-1\nevent: %s\ndata: %s\n\n", $kind, $data);
}

/**
 * One step event.
 *
 * @param  array<string, mixed>  $step
 */
function aStepEvent(array $step): string
{
    return anEventOnTheWalk('step', json_encode(aStepEnvelope($step), JSON_THROW_ON_ERROR));
}

/** The line both implementations answer with, where the walk is searching. */
function theSearchingLine(): ALineItSaid
{
    return ALineItSaid::withDetail(WalkthroughStep::Searching, 'Searching the indexers for Sintel', 'Two indexers answered');
}

/** The line both answer with, where the walk is downloading. */
function theDownloadingLine(): ALineItSaid
{
    return ALineItSaid::withoutDetail(WalkthroughStep::Downloading, 'Downloading Sintel');
}

/**
 * Both ways of following a walk, each set up to hear the same thing.
 *
 * @param  list<MockResponse>  $streams  what the far end answers each time the adapter opens the stream
 * @param  list<WhatTheWalkSaid>  $script   what the fake is scripted to answer, wake by wake
 * @return array<string, Closure(): HearingTheWalk>
 */
function everyWayOfFollowingAWalk(array $streams, array $script): array
{
    return [
        'the fake' => static fn(): HearingTheWalk => AStackThatNarrates::thenEnding(...$script),
        'the adapter' => static function () use ($streams): HearingTheWalk {
            MockClient::destroyGlobal();
            MockClient::global($streams);

            return new Narrators(new PinnedClients());
        },
    ];
}

/** Every field of what the walk said, as one line, whichever arm it took. */
function theWordForWhatTheWalkSaidAsAWord(WhatTheWalkSaid $said): string
{
    return $said->either(
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
        alive: static fn(): TheWordCarriedOut => new TheWordCarriedOut('alive'),
        said: static fn(ALineItSaid $line): TheWordCarriedOut => new TheWordCarriedOut(aLineHeardAsAWord($line)),
        closed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('closed'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** Every field of one line, as one line. */
function aLineHeardAsAWord(ALineItSaid $line): string
{
    return sprintf('%s: %s (%s)', $line->step()->value, $line->said(), $line->detail(
        said: static fn(string $detail): TheWordCarriedOut => new TheWordCarriedOut($detail),
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing particular'),
    )->said);
}

/**
 * What one way of following a walk hears across as many wakes as asked.
 *
 * @return list<string>
 */
function whatWakesOfTheWalkHear(HearingTheWalk $hearing, int $wakes): array
{
    $heard = [];

    for ($wake = 0; $wake < $wakes; $wake++) {
        $heard[] = theWordForWhatTheWalkSaidAsAWord($hearing->whereItIs(aStackAWalkRunsOn(), theSessionAWalkIsFollowedWith()));
    }

    return $heard;
}

it('hears the step a walk says, and then that the stream ended', function (): void {
    foreach (everyWayOfFollowingAWalk(
        [MockResponse::make(aStepEvent(aSearchingStep()))],
        [WhatTheWalkSaid::said(theSearchingLine())],
    ) as $which => $make) {
        expect(whatWakesOfTheWalkHear($make(), 2))->toBe([
            'searching: Searching the indexers for Sintel (Two indexers answered)',
            'closed',
        ], $which);
    }
});

it('hears a step with nothing particular to say as a line without a detail', function (): void {
    foreach (everyWayOfFollowingAWalk(
        [MockResponse::make(aStepEvent(aDownloadingStep()))],
        [WhatTheWalkSaid::said(theDownloadingLine())],
    ) as $which => $make) {
        expect(whatWakesOfTheWalkHear($make(), 1))->toBe(['downloading: Downloading Sintel (nothing particular)'], $which);
    }
});

it('hears only the last step of everything that arrived since the last wake', function (): void {
    foreach (everyWayOfFollowingAWalk(
        [MockResponse::make(sprintf('%s%s', aStepEvent(aSearchingStep()), aStepEvent(aDownloadingStep())))],
        [WhatTheWalkSaid::said(theDownloadingLine())],
    ) as $which => $make) {
        expect(whatWakesOfTheWalkHear($make(), 1))->toBe(['downloading: Downloading Sintel (nothing particular)'], $which);
    }
});

it('hears a sign of life in a heartbeat, and in an event that is not a step', function (): void {
    foreach ([": beat\n\n", anEventOnTheWalk('dashboard', '{"api_version":1,"kind":"dashboard","data":{}}')] as $said) {
        foreach (everyWayOfFollowingAWalk(
            [MockResponse::make($said)],
            [WhatTheWalkSaid::aSignOfLife()],
        ) as $which => $make) {
            expect(whatWakesOfTheWalkHear($make(), 2))->toBe(['alive', 'closed'], $which);
        }
    }
});

it('hears nothing from a stream that has said nothing yet', function (): void {
    foreach (everyWayOfFollowingAWalk(
        [MockResponse::make('')],
        [WhatTheWalkSaid::nothing()],
    ) as $which => $make) {
        expect(whatWakesOfTheWalkHear($make(), 2))->toBe(['nothing', 'closed'], $which);
    }
});

it('tells a session the stack refused from a stack that could not be heard', function (): void {
    $unreachable = MockResponse::make()->throw(static fn(PendingRequest $asked): FatalRequestException
        => new FatalRequestException(new RuntimeException('Connection refused'), $asked));

    // A connection that was never made is asked after twice more before it is
    // reported, so a stack that cannot be heard answers all three times.
    $table = [
        [[MockResponse::make('{"error":"no"}', 401)], Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [[MockResponse::make('{"error":"gone"}', 500)], Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [[$unreachable, $unreachable, $unreachable], Obstacle::of(KindOfObstacle::ConnectionWasTurnedAway)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfFollowingAWalk($answered, [WhatTheWalkSaid::met($why)]) as $which => $make) {
            expect(whatWakesOfTheWalkHear($make(), 1))->toBe([$why->kind()->value], sprintf('%s, %s', $which, $why->kind()->value));
        }
    }
});

it('cannot hear a step it cannot read, and says so as an answer it could not read', function (): void {
    foreach ([
        anEventOnTheWalk('step', 'not json at all'),
        aStepEvent(aStepTheWalkSays('dawdling', 'Doing nothing much', '')),
        aStepEvent(aStepTheWalkSays('searching', '  ', '')),
        aStepEvent(aStepTheWalkSays('searching', 'Searching the indexers for Sintel', '   ')),
        anEventOnTheWalk('step', '{"api_version":1,"kind":"step","data":"searching"}'),
    ] as $said) {
        foreach (everyWayOfFollowingAWalk(
            [MockResponse::make($said)],
            [WhatTheWalkSaid::met(Obstacle::of(KindOfObstacle::AnswerCouldNotBeRead))],
        ) as $which => $make) {
            expect(whatWakesOfTheWalkHear($make(), 1))->toBe([KindOfObstacle::AnswerCouldNotBeRead->value], $which);
        }
    }
});

it('closes when let go of, and opens again the next time it is asked', function (): void {
    foreach (everyWayOfFollowingAWalk(
        [
            MockResponse::make(aStepEvent(aSearchingStep())),
            MockResponse::make(aStepEvent(aDownloadingStep())),
        ],
        [WhatTheWalkSaid::said(theSearchingLine()), WhatTheWalkSaid::said(theDownloadingLine())],
    ) as $which => $make) {
        $hearing = $make();

        expect(whatWakesOfTheWalkHear($hearing, 1))->toHaveCount(1)
            ->and(theWordForWhatTheWalkSaidAsAWord($hearing->letGo()))->toBe('closed', $which)
            ->and(whatWakesOfTheWalkHear($hearing, 1))->toBe(['downloading: Downloading Sintel (nothing particular)'], $which);
    }
});

it('opens the stream once, and takes what arrived after that without asking again', function (): void {
    // Only the adapter can be asked this. A wake that reopened the stream to
    // read it would be a request to the stack on every wake, which is the
    // polling holding a stream exists to replace.
    MockClient::destroyGlobal();
    $stack = MockClient::global([MockResponse::make(''), MockResponse::make('')]);

    whatWakesOfTheWalkHear(new Narrators(new PinnedClients()), 2);

    $stack->assertSentCount(1);
});

it('opens a stream that ended again, once it has said that it ended', function (): void {
    // Only the adapter can be asked this: the fake's script ends where the
    // screen's cadence takes over.
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aStepEvent(aSearchingStep())),
        MockResponse::make(aStepEvent(aDownloadingStep())),
    ]);

    expect(whatWakesOfTheWalkHear(new Narrators(new PinnedClients()), 3))->toBe([
        aLineHeardAsAWord(theSearchingLine()),
        'closed',
        aLineHeardAsAWord(theDownloadingLine()),
    ])->and(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1']);
});

it('opens again straight after a stream that ended having said nothing', function (): void {
    // The end of a stream that carried nothing is said by the call that finds
    // it, so there is no second `closed` owed: the next wake opens again.
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(''),
        MockResponse::make(aStepEvent(aDownloadingStep())),
    ]);

    expect(whatWakesOfTheWalkHear(new Narrators(new PinnedClients()), 3))->toBe([
        'nothing',
        'closed',
        aLineHeardAsAWord(theDownloadingLine()),
    ]);

    $stack->assertSentCount(2);
});

it('holds nothing after a step it could not read, so the next wake opens again', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aStepEvent(aStepTheWalkSays('dawdling', 'Doing nothing much', ''))),
        MockResponse::make(aStepEvent(aDownloadingStep())),
    ]);

    expect(whatWakesOfTheWalkHear(new Narrators(new PinnedClients()), 2))->toBe([
        KindOfObstacle::AnswerCouldNotBeRead->value,
        aLineHeardAsAWord(theDownloadingLine()),
    ])->and(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1']);
});

it('forgets where the stream left off once let go of', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aStepEvent(aSearchingStep())),
        MockResponse::make(''),
    ]);
    $hearing = new Narrators(new PinnedClients());

    whatWakesOfTheWalkHear($hearing, 1);
    $hearing->letGo();
    whatWakesOfTheWalkHear($hearing, 1);

    expect(WhereEachOpenResumed::in($stack))->toBe([null, null]);
});

it('stands in for a stack with steps the contract would accept', function (): void {
    foreach ([aSearchingStep(), aDownloadingStep()] as $step) {
        expect(WhatTheContractAccepts::complaintsAbout('StepEnvelope', aStepEnvelope($step)))
            ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
    }
});
