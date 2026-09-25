<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Questions;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\WhatTheContractAccepts;

// The Asking contract, run against the adapter and against the fake.
//
// `G2`'s shape. Every test of a screen showing health will hand its subject an
// `AStackThatWasAsked` and never open a socket, so a fake easier to satisfy
// than the adapter would enforce *every obstacle says what happened and what to
// do* against a stack that always answers.
//
// Both arms are driven from the same table of what the far end did: the adapter
// is given a response, the fake is given the answer that response should
// produce, and every assertion is about the `WhatCameBack` they hand back
// rather than about how either got there.
//
// What is deliberately not asserted: which endpoint is called, and that the
// connection was pinned. The fake dials nothing, so a contract asking those
// would either fail on it or be weakened to pass — and a weakened contract is
// how a fake drifts. `Contract\Api` is where the endpoint is pinned, on the
// SDK's own side, and `NothingReachesAStackUnpinnedTest` keeps the pinning
// honest here.

afterEach(function (): void {
    // A global mock outlives the test that set it. Torn down here rather than
    // at the end of each case so a failing assertion cannot skip it.
    MockClient::destroyGlobal();
});

/** A stack to ask after, reachable and pinned so the adapter will build a client. */
function aStackToAskAfter(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session it is asked with. */
function theSessionInHand(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** The report both implementations answer with, where they answer. */
function theSameReport(): Report
{
    return Report::of(Overall::Degraded, Findings::of(
        Finding::of(
            Check::of('disk.space'),
            Category::Storage,
            'The disk is nearly full',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
            WhoPutItThere::bundled(),
        ),
    ));
}

/**
 * The payload a stack sends where the checks ran and found that.
 *
 * Separate from the response so the rule at the foot of this file reads the
 * same array the adapter is given. A fixture checked in one place and sent in
 * another is a fixture that can drift from itself.
 *
 * @return array<string, mixed>
 */
function whatADegradedStackSends(): array
{
    return aDegradedStackWhoseVerdictIs(theWarningADegradedStackSends());
}

/**
 * The warning a degraded stack's one finding carries.
 *
 * @return array<string, mixed>
 */
function theWarningADegradedStackSends(): array
{
    return [
        'outcome' => 'warn',
        'code' => 'DISK-1',
        'severity' => 'warning',
        'state' => 'guided',
        'summary' => 'Nearly full',
        'meaning' => 'New downloads will start failing soon',
        'remedies' => [['action' => 'Make room, or add a disk']],
    ];
}

/**
 * A degraded stack's answer, its one finding carrying the verdict given.
 *
 * @param array<string, mixed> $verdict
 * @return array<string, mixed>
 */
function aDegradedStackWhoseVerdictIs(array $verdict): array
{
    return [
        'api_version' => 1,
        'kind' => 'doctor',
        'data' => [
            'overall' => 'degraded',
            'findings' => [[
                'check' => 'disk.space',
                'category' => 'storage',
                'title' => 'The disk is nearly full',
                'origin' => ['origin' => 'bundled'],
                // `outcome` rather than `kind`, which is the wire's own
                // spelling for a verdict's tag — a fixture that invented a
                // shape no stack sends is a test that passes against an
                // adapter which could not read a real answer.
                'verdict' => $verdict,
            ]],
        ],
    ];
}

/** What the far end answers where the checks ran and found that. */
function aDegradedAnswer(): MockResponse
{
    return MockResponse::make((string) json_encode(whatADegradedStackSends()));
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument, so a pair returns as an array where the test wanted two
 * parameters.
 *
 * @return array<string, Closure(): Asking>
 */
function everyWayOfAsking(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Asking => $why instanceof Obstacle
            ? AStackThatWasAsked::met($why)
            : AStackThatWasAsked::saying(theSameReport()),
        'the adapter' => static function () use ($answered): Asking {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Questions(new PinnedClients());
        },
    ];
}

/**
 * One word carried out of an `either()` arm.
 *
 * `WhatCameBack::either()` answers with an object, so a caller cannot pull a
 * report out without saying what happens when there is none. A test still wants
 * to compare a string, and this is the smallest honest way across.
 */
final readonly class WhatTheStackTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** What a stack answered, as a word, whichever arm it took. */
function whatItSaid(Asking $asking): string
{
    return $asking->about(aStackToAskAfter(), theSessionInHand())->either(
        said: static fn(Report $report): WhatTheStackTurnedOutToSay => new WhatTheStackTurnedOutToSay(
            sprintf('%s with %d', $report->overall()->value, $report->findings()->count()),
        ),
        met: static fn(Obstacle $why): WhatTheStackTurnedOutToSay => new WhatTheStackTurnedOutToSay($why->value),
    )->said;
}

it('an adapter answers a verdict it cannot build with an obstacle', function (): void {
    $warned = theWarningADegradedStackSends();

    $spoiled = [
        'a blank code' => [...$warned, 'code' => ''],
        'a blank meaning' => [...$warned, 'meaning' => ' '],
        'a blank remedy' => [...$warned, 'remedies' => [['action' => '']]],
        'an unverified check with a blank reason' => ['outcome' => 'unverified', 'reason' => ''],
        'a remedy for an unverified check that says nothing' => [
            'outcome' => 'unverified',
            'reason' => 'The check needs a disk it could not find',
            'remedy' => ['action' => ''],
        ],
    ];

    foreach ($spoiled as $which => $verdict) {
        MockClient::destroyGlobal();
        MockClient::global([MockResponse::make((string) json_encode(aDegradedStackWhoseVerdictIs($verdict)))]);

        expect(whatItSaid(new Questions(new PinnedClients())))->toBe(Obstacle::StackDidNotAnswer->value, $which);
    }
});

it('comes away with what the checks found, and what it amounts to', function (): void {
    // Both halves, because a screen needs both and the top one is not derived:
    // an app working the word out from the findings would be a second opinion
    // about a judgement the engine already made.
    foreach (everyWayOfAsking(aDegradedAnswer()) as $which => $make) {
        expect(whatItSaid($make()))->toBe('degraded with 1', $which);
    }
});

it('N1-R10 — tells a session that has ended from a stack that is not answering', function (): void {
    // The one distinction worth drawing here, and the reason this reads a
    // status rather than treating every refusal alike: a session the stack will
    // not accept is answered by signing in again, on a machine that is working
    // perfectly. Everything else is the machine, and the remedy is to go and
    // look at it.
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAsking($answered, $why) as $which => $make) {
            expect(whatItSaid($make()))->toBe($why->value, sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('answers exactly one way, and answers at all', function (): void {
    // What a single-arm assertion cannot catch. An implementation taking both
    // arms would render a report and an obstacle on one screen; one taking
    // neither would leave an operator looking at a machine that said nothing.
    foreach (everyWayOfAsking(aDegradedAnswer()) as $which => $make) {
        $arms = 0;
        $count = static function () use (&$arms): WhatTheStackTurnedOutToSay {
            $arms++;

            return new WhatTheStackTurnedOutToSay('counted');
        };

        $make()->about(aStackToAskAfter(), theSessionInHand())->either(said: $count, met: $count);

        expect($arms)->toBe(1, $which);
    }
});

it('N1-R65 — asks once, because a frame reads a machine once', function (): void {
    // Only the fake can be asked this, and it is asked because every screen
    // test will trust the answer. A port asked twice per frame is four
    // connections to a machine over somebody's home network.
    $asking = AStackThatWasAsked::saying(theSameReport());
    $stack = aStackToAskAfter();

    $asking->about($stack, theSessionInHand());

    expect($asking->askedAbout())->toBe($stack)
        ->and($asking->askings())->toBe(1);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('DoctorEnvelope', whatADegradedStackSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
