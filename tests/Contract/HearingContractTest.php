<?php

declare(strict_types=1);

use Modules\Dx\Internal\WhatAStackWouldSay;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\AStoppage;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatAStartWaitsOn;
use Modules\Kernel\Api\WhatFollowedFromIt;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Sdk\Api\Listeners;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;
use Tests\Support\WhereEachOpenResumed;

// The Hearing contract, run against the adapter and against the fake.
//
// Every screen test that shows the health summary will hand its subject an
// `AStackThatSpeaksUp` and never open a stream, so a fake that answered in a
// way the adapter never does would let a screen pass against a stack that
// cannot exist.
//
// The adapter is given a stream that says its piece and ends, because that is
// the only kind a mocked response can be. What it answers across two wakes is
// what the fake is scripted to answer, and every assertion is about those
// answers rather than about how either produced them.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** A stack to listen to, reachable and pinned so the adapter will build a client. */
function aStackToListenTo(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('c', Nonce::SHORTEST))),
        StackName::of('The attic'),
        Address::of('https://192.168.1.43:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

/** The session it is listened to with. */
function theSessionItListensWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * The health summary a stack with a filling disk sends.
 *
 * @return array<string, mixed>
 */
function whatAFillingStackSaysOfItsHealth(): array
{
    return [
        'standing' => 'degraded',
        'wanting_attention' => 1,
        'worst' => 'The disk is nearly full',
        'affected' => [[
            'check' => 'disk.space',
            'severity' => 'warning',
            'summary' => 'The disk is nearly full',
            'meaning' => 'New downloads will start failing soon',
            'remedies' => ['Make room', 'Add a disk'],
            'downstream' => ['Imports are failing'],
        ]],
    ];
}

/**
 * The health summary a stack with nothing wrong sends, naming nothing.
 *
 * @return array<string, mixed>
 */
function whatAHealthyStackSaysOfItsHealth(): array
{
    return [
        'standing' => 'healthy',
        'wanting_attention' => 0,
        'worst' => null,
        'affected' => [],
    ];
}

/**
 * A whole dashboard carrying one health summary, and what stopped moving.
 *
 * The rest of the dashboard is what the stand-in builds from the contract,
 * because it is read by nothing here and only has to be a dashboard a stack
 * could send.
 *
 * @param  array<string, mixed>  $health
 * @param  list<array<string, mixed>>  $stuck
 * @return array<string, mixed>
 */
function aDashboardCarrying(array $health, array $stuck = []): array
{
    $rest = WhatAStackWouldSay::inside('DashboardEnvelope');

    return [
        'api_version' => 1,
        'kind' => 'dashboard',
        'data' => [...(is_array($rest) ? $rest : []), 'health' => $health, 'stuck' => $stuck],
    ];
}

/** One event as the core frames it. */
function anEvent(string $kind, string $data): string
{
    return sprintf("id: run-1\nevent: %s\ndata: %s\n\n", $kind, $data);
}

/**
 * One dashboard event carrying one health summary, and what stopped moving.
 *
 * @param  array<string, mixed>  $health
 * @param  list<array<string, mixed>>  $stuck
 */
function aDashboardEvent(array $health, array $stuck = []): string
{
    return anEvent('dashboard', json_encode(aDashboardCarrying($health, $stuck), JSON_THROW_ON_ERROR));
}

/** The summary both implementations answer with, where a filling stack speaks. */
function theFillingSummary(): TheHealthSummary
{
    return TheHealthSummary::of(
        HowItStands::Degraded,
        1,
        'The disk is nearly full',
        WhatStoppedMoving::nothing(),
        AnAffectedItem::of(
            Check::of('disk.space'),
            Severity::Warning,
            'The disk is nearly full',
            'New downloads will start failing soon',
            Remedies::of(Remedy::of('Make room'), Remedy::of('Add a disk')),
            WhatFollowedFromIt::of('Imports are failing'),
        ),
    );
}

/**
 * One stopped row, a cause several items share, as a stack sends it.
 *
 * @return array<string, mixed>
 */
function aStoppedQueueRow(): array
{
    return [
        'stall' => 'repeated-import-failure',
        'name' => 'Permission denied on /media/films',
        'items' => 20,
        'blocking' => 'Access to the path is denied.',
        'held_for' => 10_800,
    ];
}

/** The summary both answer with, where a healthy stack speaks. */
function theHealthySummary(): TheHealthSummary
{
    return TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing());
}

/**
 * Both ways of listening, each set up to hear the same thing.
 *
 * @param  list<MockResponse>  $streams  what the far end answers each time the adapter opens the stream
 * @param  list<WhatWasHeard>  $script   what the fake is scripted to answer, wake by wake
 * @return array<string, Closure(): Hearing>
 */
function everyWayOfListening(array $streams, array $script): array
{
    return [
        'the fake' => static fn(): Hearing => AStackThatSpeaksUp::thenEnding(...$script),
        'the adapter' => static function () use ($streams): Hearing {
            MockClient::destroyGlobal();
            MockClient::global($streams);

            return new Listeners(new PinnedClients());
        },
    ];
}

/** Every field of what was heard, as one line, whichever arm it took. */
function whatWasHeardAsAWord(WhatWasHeard $heard): string
{
    return $heard->either(
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
        alive: static fn(): TheWordCarriedOut => new TheWordCarriedOut('alive'),
        said: static fn(TheHealthSummary $summary): TheWordCarriedOut => new TheWordCarriedOut(aSummaryAsAWord($summary)),
        closed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('closed'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** Every field of a summary, as one line. */
function aSummaryAsAWord(TheHealthSummary $summary): string
{
    $items = [];

    foreach ($summary as $item) {
        $remedies = [];

        foreach ($item->remedies() as $remedy) {
            $remedies[] = $remedy->action();
        }

        $items[] = sprintf(
            '%s/%s/%s/%s/[%s]/[%s]',
            $item->check()->shown(),
            $item->severity()->value,
            $item->summary(),
            $item->meaning(),
            implode('|', $remedies),
            implode('|', iterator_to_array($item->downstream(), preserve_keys: false)),
        );
    }

    $stopped = [];

    foreach ($summary->stopped() as $row) {
        $stopped[] = sprintf('%s/%s/%d/%s/%ds', $row->how()->value, $row->name(), $row->items(), $row->blocking(), $row->heldFor()->inSeconds());
    }

    return sprintf(
        '%s, %d wanting, worst "%s", {%s}%s',
        $summary->standing()->value,
        $summary->wantingAttention(),
        $summary->worst(),
        implode(';', $items),
        $stopped === [] ? '' : sprintf(', stopped {%s}', implode(';', $stopped)),
    );
}

/**
 * What one way of listening hears across as many wakes as asked.
 *
 * @return list<string>
 */
function whatWakesHear(Hearing $hearing, int $wakes): array
{
    $heard = [];

    for ($wake = 0; $wake < $wakes; $wake++) {
        $heard[] = whatWasHeardAsAWord($hearing->howItIs(aStackToListenTo(), theSessionItListensWith()));
    }

    return $heard;
}

it('hears the summary a stack says, and then that the stream ended', function (): void {
    foreach (everyWayOfListening(
        [MockResponse::make(aDashboardEvent(whatAFillingStackSaysOfItsHealth()))],
        [WhatWasHeard::said(theFillingSummary())],
    ) as $which => $make) {
        expect(whatWakesHear($make(), 2))->toBe([
            'degraded, 1 wanting, worst "The disk is nearly full", {disk.space/warning/The disk is nearly full/New downloads will start failing soon/[Make room|Add a disk]/[Imports are failing]}',
            'closed',
        ], $which);
    }
});

it('hears a healthy stack name nothing as its worst, whether it leaves the name out or sends none', function (): void {
    $leftOut = whatAHealthyStackSaysOfItsHealth();
    unset($leftOut['worst']);

    foreach ([whatAHealthyStackSaysOfItsHealth(), $leftOut] as $health) {
        foreach (everyWayOfListening(
            [MockResponse::make(aDashboardEvent($health))],
            [WhatWasHeard::said(theHealthySummary())],
        ) as $which => $make) {
            expect(whatWakesHear($make(), 1))->toBe(['healthy, 0 wanting, worst "", {}'], $which);
        }
    }
});

it('hears only the last summary of everything that arrived since the last wake', function (): void {
    foreach (everyWayOfListening(
        [MockResponse::make(sprintf('%s%s', aDashboardEvent(whatAFillingStackSaysOfItsHealth()), aDashboardEvent(whatAHealthyStackSaysOfItsHealth())))],
        [WhatWasHeard::said(theHealthySummary())],
    ) as $which => $make) {
        expect(whatWakesHear($make(), 1))->toBe(['healthy, 0 wanting, worst "", {}'], $which);
    }
});

it('hears a sign of life in a heartbeat, and in an event that is not a summary', function (): void {
    foreach ([": beat\n\n", anEvent('step', '{"api_version":1,"kind":"step","data":{}}')] as $said) {
        foreach (everyWayOfListening(
            [MockResponse::make($said)],
            [WhatWasHeard::aSignOfLife()],
        ) as $which => $make) {
            expect(whatWakesHear($make(), 2))->toBe(['alive', 'closed'], $which);
        }
    }
});

it('hears nothing from a stream that has said nothing yet', function (): void {
    foreach (everyWayOfListening(
        [MockResponse::make('')],
        [WhatWasHeard::nothing()],
    ) as $which => $make) {
        expect(whatWakesHear($make(), 2))->toBe(['nothing', 'closed'], $which);
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
        [[$unreachable, $unreachable, $unreachable], Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfListening($answered, [WhatWasHeard::met($why)]) as $which => $make) {
            expect(whatWakesHear($make(), 1))->toBe([$why->kind()->value], sprintf('%s, %s', $which, $why->kind()->value));
        }
    }
});

it('cannot hear a summary it cannot read, and says so as a stack that did not answer', function (): void {
    $unknownWord = [...whatAHealthyStackSaysOfItsHealth(), 'standing' => 'splendid'];
    $belowNothing = [...whatAHealthyStackSaysOfItsHealth(), 'wanting_attention' => -1];
    $anUnknownStall = [...aStoppedQueueRow(), 'stall' => 'sulking'];
    $aRowForNothing = [...aStoppedQueueRow(), 'items' => 0];

    foreach ([
        anEvent('dashboard', 'not json at all'),
        aDashboardEvent($unknownWord),
        aDashboardEvent($belowNothing),
        aDashboardEvent(whatAHealthyStackSaysOfItsHealth(), [$anUnknownStall]),
        aDashboardEvent(whatAHealthyStackSaysOfItsHealth(), [$aRowForNothing]),
    ] as $said) {
        foreach (everyWayOfListening(
            [MockResponse::make($said)],
            [WhatWasHeard::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))],
        ) as $which => $make) {
            expect(whatWakesHear($make(), 1))->toBe([KindOfObstacle::StackDidNotAnswer->value], $which);
        }
    }
});

it('closes when let go of, and opens again the next time it is asked', function (): void {
    foreach (everyWayOfListening(
        [
            MockResponse::make(aDashboardEvent(whatAFillingStackSaysOfItsHealth())),
            MockResponse::make(aDashboardEvent(whatAHealthyStackSaysOfItsHealth())),
        ],
        [WhatWasHeard::said(theFillingSummary()), WhatWasHeard::said(theHealthySummary())],
    ) as $which => $make) {
        $hearing = $make();

        expect(whatWakesHear($hearing, 1))->toHaveCount(1)
            ->and(whatWasHeardAsAWord($hearing->letGo()))->toBe('closed', $which)
            ->and(whatWakesHear($hearing, 1))->toBe(['healthy, 0 wanting, worst "", {}'], $which);
    }
});

it('opens the stream once, and takes what arrived after that without asking again', function (): void {
    // Only the adapter can be asked this. A wake that reopened the stream to
    // read it would be a request to the stack every two seconds, which is the
    // polling holding a stream exists to replace.
    MockClient::destroyGlobal();
    $stack = MockClient::global([MockResponse::make(''), MockResponse::make('')]);
    $hearing = new Listeners(new PinnedClients());

    whatWakesHear($hearing, 2);

    $stack->assertSentCount(1);
});

/** A second stack, listened to beside the first. */
function anotherStackToListenTo(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The shed'),
        Address::of('https://192.168.1.44:8443'),
        Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
    );
}

it('holds one stream for each stack it is asked about, and opens neither again', function (): void {
    // Only the adapter can be asked this. The list of stacks listens to every
    // stack it is signed into through one of these, and a wake that reopened
    // one stack's stream because another was asked about in between would be
    // a request to each stack on every wake.
    MockClient::destroyGlobal();
    $stack = MockClient::global([MockResponse::make(''), MockResponse::make('')]);
    $hearing = new Listeners(new PinnedClients());

    foreach ([aStackToListenTo(), anotherStackToListenTo(), aStackToListenTo(), anotherStackToListenTo()] as $asked) {
        $hearing->howItIs($asked, theSessionItListensWith());
    }

    $stack->assertSentCount(2);
});

it('leaves every other stack\'s stream open when one stack could not be heard', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([MockResponse::make(''), MockResponse::make('{"error":"gone"}', 500)]);
    $hearing = new Listeners(new PinnedClients());

    $first = $hearing->howItIs(aStackToListenTo(), theSessionItListensWith());
    $second = $hearing->howItIs(anotherStackToListenTo(), theSessionItListensWith());
    $again = $hearing->howItIs(aStackToListenTo(), theSessionItListensWith());

    // The first stack's stream is the one it opened: a mocked stream ends once
    // its bytes are read, and a stream opened again would have been a third
    // request.
    expect(array_map(whatWasHeardAsAWord(...), [$first, $second, $again]))
        ->toBe(['nothing', KindOfObstacle::StackDidNotAnswer->value, 'closed']);

    $stack->assertSentCount(2);
});

it('opens a stream that ended again, once it has said that it ended', function (): void {
    // Only the adapter can be asked this: the fake's script ends where the
    // screen's cadence takes over. What is held is the promise that an ended
    // stream is said to have ended exactly once, and then opened again.
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aDashboardEvent(whatAFillingStackSaysOfItsHealth())),
        MockResponse::make(aDashboardEvent(whatAHealthyStackSaysOfItsHealth())),
    ]);

    expect(whatWakesHear(new Listeners(new PinnedClients()), 3))->toBe([
        aSummaryAsAWord(theFillingSummary()),
        'closed',
        'healthy, 0 wanting, worst "", {}',
    ])->and(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1']);
});

it('resumes a stream it could not read after the last event it carried, and forgets that once let go of', function (): void {
    // Only the adapter can be asked this. What the stack said while no stream
    // was open is heard on the next one, rather than skipped; a screen that
    // let go of every stream starts again from the start.
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(anEvent('dashboard', 'not an envelope')),
        MockResponse::make(''),
        MockResponse::make(''),
    ]);
    $hearing = new Listeners(new PinnedClients());

    whatWakesHear($hearing, 2);
    $hearing->letGo();
    whatWakesHear($hearing, 1);

    expect(WhereEachOpenResumed::in($stack))->toBe([null, 'run-1', null]);
});

it('opens again straight after a stream that ended having said nothing', function (): void {
    // The end of a stream that carried nothing is said by the call that finds
    // it, so there is no second `closed` owed: the next wake opens again.
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(''),
        MockResponse::make(aDashboardEvent(whatAHealthyStackSaysOfItsHealth())),
    ]);

    expect(whatWakesHear(new Listeners(new PinnedClients()), 3))->toBe([
        'nothing',
        'closed',
        'healthy, 0 wanting, worst "", {}',
    ]);

    $stack->assertSentCount(2);
});

it('holds nothing after a summary it could not read, so the next wake opens again', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([
        MockResponse::make(aDashboardEvent([...whatAHealthyStackSaysOfItsHealth(), 'standing' => 'splendid'])),
        MockResponse::make(aDashboardEvent(whatAHealthyStackSaysOfItsHealth())),
    ]);

    expect(whatWakesHear(new Listeners(new PinnedClients()), 2))->toBe([
        KindOfObstacle::StackDidNotAnswer->value,
        'healthy, 0 wanting, worst "", {}',
    ]);
});

it('waits no longer than a millisecond for bytes that have not arrived', function (): void {
    // The one number that keeps a wake from waiting on the stack. Longer, and
    // every wake of the screen holds the only thread for that long.
    expect(Listeners::NO_LONGER_THAN_MS)->toBe(1);
});

it('stands in for a stack with start lines the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StartEnvelope', ['api_version' => 1, 'kind' => 'start', 'data' => 'Waiting for the database']))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});

it('stands in for a stack with dashboards the contract would accept', function (): void {
    foreach ([whatAFillingStackSaysOfItsHealth(), whatAHealthyStackSaysOfItsHealth()] as $health) {
        expect(WhatTheContractAccepts::complaintsAbout('DashboardEnvelope', aDashboardCarrying($health)))
            ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
    }
});

it('hears what stopped moving with the summary, in the order the stack sent it', function (): void {
    $slow = ['stall' => 'slow', 'name' => 'Dune', 'items' => 1, 'blocking' => null, 'held_for' => 600];

    foreach (everyWayOfListening(
        [MockResponse::make(aDashboardEvent(whatAHealthyStackSaysOfItsHealth(), [aStoppedQueueRow(), $slow]))],
        [WhatWasHeard::said(TheHealthSummary::of(
            HowItStands::Healthy,
            0,
            '',
            WhatStoppedMoving::of(
                AStoppage::of(HowItStopped::RepeatedImportFailure, 'Permission denied on /media/films', 20, 'Access to the path is denied.', 10_800),
                AStoppage::of(HowItStopped::Slow, 'Dune', 1, '', 600),
            ),
        ))],
    ) as $which => $make) {
        expect(whatWakesHear($make(), 1))->toBe([
            'healthy, 0 wanting, worst "", {}, stopped {repeated-import-failure/Permission denied on /media/films/20/Access to the path is denied./10800s;slow/Dune/1//600s}',
        ], $which);
    }
});

/** One start line as the core frames it, carrying whatever payload a test names. */
function aStartLine(mixed $said): string
{
    return anEvent('start', json_encode(['api_version' => 1, 'kind' => 'start', 'data' => $said], JSON_THROW_ON_ERROR));
}

/**
 * Both ways of hearing a start, each set up to hear the same thing.
 *
 * @param  list<MockResponse>  $streams  what the far end answers each time the adapter opens the stream
 * @param  list<WhatAStartWaitsOn>  $script  what the fake is scripted to answer, wake by wake
 * @return array<string, Closure(): Hearing>
 */
function everyWayOfHearingAStart(array $streams, array $script): array
{
    return [
        'the fake' => static fn(): Hearing => AStackThatSpeaksUp::whileItStarts(...$script),
        'the adapter' => static function () use ($streams): Hearing {
            MockClient::destroyGlobal();
            MockClient::global($streams);

            return new Listeners(new PinnedClients());
        },
    ];
}

/**
 * What one way of hearing a start answers across as many wakes as asked.
 *
 * @return list<string>
 */
function whatWakesHearOfAStart(Hearing $hearing, int $wakes): array
{
    $heard = [];

    for ($wake = 0; $wake < $wakes; $wake++) {
        $heard[] = $hearing->whatAStartWaitsOn(aStackToListenTo(), theSessionItListensWith())->either(
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
            MockResponse::make(sprintf('%s%s%s', aStartLine('Waiting for the database'), aDashboardEvent(whatAHealthyStackSaysOfItsHealth()), aStartLine('Waiting for sonarr to answer'))),
            MockResponse::make(''),
        ],
        [WhatAStartWaitsOn::saying('Waiting for sonarr to answer')],
    ) as $which => $make) {
        expect(whatWakesHearOfAStart($make(), 2))->toBe(['saying "Waiting for sonarr to answer"', 'nothing new'], $which);
    }
});

it('hears nothing new from a stream that carried no start line', function (): void {
    foreach (everyWayOfHearingAStart(
        [MockResponse::make(aDashboardEvent(whatAHealthyStackSaysOfItsHealth()))],
        [],
    ) as $which => $make) {
        expect(whatWakesHearOfAStart($make(), 1))->toBe(['nothing new'], $which);
    }
});

it('cannot hear a start line that is blank or not text, and says so as a stack that did not answer', function (): void {
    foreach ([aStartLine('   '), aStartLine(['waiting' => 'for sonarr']), anEvent('start', 'not json at all')] as $said) {
        foreach (everyWayOfHearingAStart(
            [MockResponse::make($said)],
            [WhatAStartWaitsOn::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))],
        ) as $which => $make) {
            expect(whatWakesHearOfAStart($make(), 1))->toBe([KindOfObstacle::StackDidNotAnswer->value], $which);
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
function aStreamStillOpenAfter(string $said): string
{
    return sprintf("%s:%s\n", $said, str_repeat(' ', 8192 - strlen($said) - 2));
}

it('opens a start stream once while it runs, and takes what arrived after that without asking again', function (): void {
    // Only the adapter can be asked this. A wake that reopened the stream to
    // read it would be a request to the stack every time the screen settles.
    MockClient::destroyGlobal();
    $stack = MockClient::global([MockResponse::make(aStreamStillOpenAfter(aStartLine('Waiting for the database'))), MockResponse::make('')]);

    expect(whatWakesHearOfAStart(new Listeners(new PinnedClients()), 2))
        ->toBe(['saying "Waiting for the database"', 'nothing new']);

    $stack->assertSentCount(1);
});

it('opens a start stream that ended again on the next ask', function (): void {
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aStreamStillOpenAfter(aStartLine('Waiting for the database'))),
        MockResponse::make(aStartLine('Waiting for sonarr to answer')),
    ]);

    expect(whatWakesHearOfAStart(new Listeners(new PinnedClients()), 3))
        ->toBe(['saying "Waiting for the database"', 'nothing new', 'saying "Waiting for sonarr to answer"']);

    $stack->assertSentCount(2);
});

it('holds no start stream after a line it could not read, so the next ask opens again', function (): void {
    // The stream is still open when the unreadable line arrives, so nothing
    // but the refusal itself lets go of it.
    MockClient::destroyGlobal();
    $stack = MockClient::global([
        MockResponse::make(aStreamStillOpenAfter(aStartLine('   '))),
        MockResponse::make(aStartLine('Waiting for sonarr to answer')),
    ]);

    expect(whatWakesHearOfAStart(new Listeners(new PinnedClients()), 2))
        ->toBe([KindOfObstacle::StackDidNotAnswer->value, 'saying "Waiting for sonarr to answer"']);

    $stack->assertSentCount(2);
});
