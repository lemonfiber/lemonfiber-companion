<?php

declare(strict_types=1);

use Modules\Dx\Internal\WhatAStackWouldSay;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\HowItStands;
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
use Modules\Kernel\Api\WhatFollowedFromIt;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Sdk\Api\Listeners;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AStackThatSpeaksUp;
use Tests\Support\WhatTheContractAccepts;

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
 * A whole dashboard carrying one health summary.
 *
 * The rest of the dashboard is what the stand-in builds from the contract,
 * because it is read by nothing here and only has to be a dashboard a stack
 * could send.
 *
 * @param  array<string, mixed>  $health
 * @return array<string, mixed>
 */
function aDashboardCarrying(array $health): array
{
    $rest = WhatAStackWouldSay::inside('DashboardEnvelope');

    return [
        'api_version' => 1,
        'kind' => 'dashboard',
        'data' => [...(is_array($rest) ? $rest : []), 'health' => $health],
    ];
}

/** One event as the core frames it. */
function anEvent(string $kind, string $data): string
{
    return sprintf("id: run-1\nevent: %s\ndata: %s\n\n", $kind, $data);
}

/**
 * One dashboard event carrying one health summary.
 *
 * @param  array<string, mixed>  $health
 */
function aDashboardEvent(array $health): string
{
    return anEvent('dashboard', json_encode(aDashboardCarrying($health), JSON_THROW_ON_ERROR));
}

/** The summary both implementations answer with, where a filling stack speaks. */
function theFillingSummary(): TheHealthSummary
{
    return TheHealthSummary::of(
        HowItStands::Degraded,
        1,
        'The disk is nearly full',
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

/** The summary both answer with, where a healthy stack speaks. */
function theHealthySummary(): TheHealthSummary
{
    return TheHealthSummary::of(HowItStands::Healthy, 0, '');
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

/** One word carried out of an `either()` arm. */
final readonly class WhatTheStreamSaidAsAWord
{
    public function __construct(public string $said) {}
}

/** Every field of what was heard, as one line, whichever arm it took. */
function whatWasHeardAsAWord(WhatWasHeard $heard): string
{
    return $heard->either(
        nothing: static fn(): WhatTheStreamSaidAsAWord => new WhatTheStreamSaidAsAWord('nothing'),
        alive: static fn(): WhatTheStreamSaidAsAWord => new WhatTheStreamSaidAsAWord('alive'),
        said: static fn(TheHealthSummary $summary): WhatTheStreamSaidAsAWord => new WhatTheStreamSaidAsAWord(aSummaryAsAWord($summary)),
        closed: static fn(): WhatTheStreamSaidAsAWord => new WhatTheStreamSaidAsAWord('closed'),
        met: static fn(Obstacle $why): WhatTheStreamSaidAsAWord => new WhatTheStreamSaidAsAWord($why->value),
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

    return sprintf(
        '%s, %d wanting, worst "%s", {%s}',
        $summary->standing()->value,
        $summary->wantingAttention(),
        $summary->worst(),
        implode(';', $items),
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
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make()->throw(static fn(PendingRequest $asked): FatalRequestException
            => new FatalRequestException(new RuntimeException('Connection refused'), $asked)), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfListening([$answered], [WhatWasHeard::met($why)]) as $which => $make) {
            expect(whatWakesHear($make(), 1))->toBe([$why->value], sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('cannot hear a summary it cannot read, and says so as a stack that did not answer', function (): void {
    $unknownWord = [...whatAHealthyStackSaysOfItsHealth(), 'standing' => 'splendid'];
    $belowNothing = [...whatAHealthyStackSaysOfItsHealth(), 'wanting_attention' => -1];

    foreach ([
        anEvent('dashboard', 'not json at all'),
        aDashboardEvent($unknownWord),
        aDashboardEvent($belowNothing),
    ] as $said) {
        foreach (everyWayOfListening(
            [MockResponse::make($said)],
            [WhatWasHeard::met(Obstacle::StackDidNotAnswer)],
        ) as $which => $make) {
            expect(whatWakesHear($make(), 1))->toBe([Obstacle::StackDidNotAnswer->value], $which);
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
        Obstacle::StackDidNotAnswer->value,
        'healthy, 0 wanting, worst "", {}',
    ]);
});

it('waits no longer than a millisecond for bytes that have not arrived', function (): void {
    // The one number that keeps a wake from waiting on the stack. Longer, and
    // every wake of the screen holds the only thread for that long.
    expect(Listeners::NO_LONGER_THAN_MS)->toBe(1);
});

it('stands in for a stack with dashboards the contract would accept', function (): void {
    foreach ([whatAFillingStackSaysOfItsHealth(), whatAHealthyStackSaysOfItsHealth()] as $health) {
        expect(WhatTheContractAccepts::complaintsAbout('DashboardEnvelope', aDashboardCarrying($health)))
            ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
    }
});
