<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Said;
use Modules\Kernel\Api\Saying;
use Modules\Kernel\Api\Scrollback;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stream;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Scrollbacks;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AServiceThatSpoke;
use Tests\Support\WhatTheContractAccepts;

// The Saying contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `StallingContractTest`'s argument one endpoint along: every
// test of the log screen will hand its subject an `AServiceThatSpoke` and never
// open a socket, so a fake easier to satisfy than the adapter would enforce
// `N2-R10` against a service that always answers.
//
// What is deliberately not asserted, as there: which endpoint is called, and
// that the connection was pinned. The fake dials nothing, so a contract asking
// those would either fail on it or be weakened to pass.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The machine whose service is read. */
function aStackWithAServiceTalking(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session it is read with. */
function theSessionTheLogsAreReadWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** The service both implementations are asked about. */
function theServiceBeingRead(): ServiceId
{
    return ServiceId::called('gluetun');
}

/** The bound both are given, and small enough that three lines fill it. */
function theBoundBothAreGiven(): HowManyLines
{
    return HowManyLines::of(3);
}

/** What both implementations answer with, where they answer. */
function theSameScrollback(): Scrollback
{
    $service = theServiceBeingRead();

    return Scrollback::of(
        $service,
        theBoundBothAreGiven(),
        Said::at('2026-09-14T04:00:00Z', 'tunnel up', $service, Stream::Stdout),
        Said::whenever('no route to host', $service, Stream::Stderr),
        Said::at('2026-09-14T04:00:02Z', 'retrying', $service, Stream::Stdout),
    );
}

/**
 * The payloads a service that spoke sends: one `log` document a line.
 *
 * Separate from the response so the rule at the foot of this file reads the
 * same arrays the adapter is given. A fixture checked in one place and sent in
 * another is a fixture that can drift from itself.
 *
 * A document each rather than one document holding the lines, because that is
 * what the endpoint streams — a fixture that gathered them into an array would
 * be a sample of a read this app never performs.
 *
 * @return list<array<string, mixed>>
 */
function whatAServiceThatSpokeSends(): array
{
    return [
        ['api_version' => 1, 'kind' => 'log', 'data' => [
            'at' => '2026-09-14T04:00:00Z', 'line' => 'tunnel up', 'service' => 'gluetun', 'stream' => 'stdout',
        ]],
        ['api_version' => 1, 'kind' => 'log', 'data' => [
            'at' => null, 'line' => 'no route to host', 'service' => 'gluetun', 'stream' => 'stderr',
        ]],
        ['api_version' => 1, 'kind' => 'log', 'data' => [
            'at' => '2026-09-14T04:00:02Z', 'line' => 'retrying', 'service' => 'gluetun', 'stream' => 'stdout',
        ]],
    ];
}

/** What the far end answers: one `log` document a line, as the read renders it. */
function aScrollbackAnswer(): MockResponse
{
    $body = '';

    foreach (whatAServiceThatSpokeSends() as $line) {
        $body .= sprintf("%s\n", (string) json_encode($line));
    }

    return MockResponse::make($body);
}

/**
 * Both ways of reading, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Closure(): Saying>
 */
function everyWayOfReadingAService(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Saying => $why instanceof Obstacle
            ? AServiceThatSpoke::met($why)
            : AServiceThatSpoke::saying(theSameScrollback()),
        'the adapter' => static function () use ($answered): Saying {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Scrollbacks(new PinnedClients());
        },
    ];
}

/** One word carried out of an `either()` arm. */
final readonly class WhatTheServiceTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Every line, folded to a word each, so an order can be compared. */
function everyLineOf(Saying $saying): string
{
    return $saying->saidBy(
        aStackWithAServiceTalking(),
        theSessionTheLogsAreReadWith(),
        theServiceBeingRead(),
        theBoundBothAreGiven(),
    )->either(
        this_: static function (Scrollback $scrollback): WhatTheServiceTurnedOutToSay {
            $rows = [];

            foreach ($scrollback as $line) {
                $rows[] = sprintf(
                    '%s/%s/%s',
                    $line->when(
                        then: static fn(string $when): WhatTheServiceTurnedOutToSay
                            => new WhatTheServiceTurnedOutToSay($when),
                        unstated: static fn(): WhatTheServiceTurnedOutToSay
                            => new WhatTheServiceTurnedOutToSay('-'),
                    )->said,
                    $line->stream()->value,
                    $line->line(),
                );
            }

            return new WhatTheServiceTurnedOutToSay(implode(' | ', $rows));
        },
        met: static fn(Obstacle $why): WhatTheServiceTurnedOutToSay
            => new WhatTheServiceTurnedOutToSay($why->value),
    )->said;
}

/** What the window claims about its own edge, whichever arm it took. */
function whatTheWindowClaims(Saying $saying): string
{
    return $saying->saidBy(
        aStackWithAServiceTalking(),
        theSessionTheLogsAreReadWith(),
        theServiceBeingRead(),
        theBoundBothAreGiven(),
    )->either(
        this_: static fn(Scrollback $scrollback): WhatTheServiceTurnedOutToSay => new WhatTheServiceTurnedOutToSay(sprintf(
            '%s %d of %d, %s',
            $scrollback->service()->named(),
            $scrollback->howManyArrived(),
            $scrollback->asked()->figure(),
            $scrollback->isAWindow() ? 'a window' : 'the whole',
        )),
        met: static fn(Obstacle $why): WhatTheServiceTurnedOutToSay
            => new WhatTheServiceTurnedOutToSay($why->value),
    )->said;
}

it('N2-R10 — comes away with the lines, in the order the service wrote them', function (): void {
    // Oldest first, which is the only order that means anything: a log line is
    // read against the line before it.
    foreach (everyWayOfReadingAService(aScrollbackAnswer()) as $which => $make) {
        expect(everyLineOf($make()))->toBe(
            '2026-09-14T04:00:00Z/stdout/tunnel up | -/stderr/no route to host | 2026-09-14T04:00:02Z/stdout/retrying',
            $which,
        );
    }
});

it('N2-R10 — names the service and says the view is a window rather than the whole', function (): void {
    // Three clauses of the requirement in one assertion, because they are one
    // sentence: the bound that was given, what arrived against it, and the
    // service it is all about.
    foreach (everyWayOfReadingAService(aScrollbackAnswer()) as $which => $make) {
        expect(whatTheWindowClaims($make()))->toBe('gluetun 3 of 3, a window', $which);
    }
});

it('N1-R10 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfReadingAService($answered, $why) as $which => $make) {
            expect(everyLineOf($make()))->toBe($why->value, $which);
        }
    }
});

it('a line this app cannot read is a stack that did not answer', function (): void {
    // A line missing its stream. The adapter refuses it, and the refusal has to
    // reach the screen as an obstacle rather than as a raise — a window one
    // line short is worse than no window, because the missing line is the one
    // somebody went looking for.
    $answered = MockResponse::make(
        sprintf("%s\n", (string) json_encode([
            'api_version' => 1,
            'kind' => 'log',
            'data' => ['at' => null, 'line' => 'tunnel up', 'service' => 'gluetun'],
        ])),
    );

    foreach (everyWayOfReadingAService($answered, Obstacle::StackDidNotAnswer) as $which => $make) {
        expect(everyLineOf($make()))->toBe(Obstacle::StackDidNotAnswer->value, $which);
    }
});

it('stands in for a service with payloads the contract would accept', function (): void {
    // Every line, not the first: a stream is read a document at a time, and the
    // one a fixture gets wrong is the one that carries the field the others
    // leave at its usual value — here, the line with no timestamp.
    foreach (whatAServiceThatSpokeSends() as $at => $line) {
        expect(WhatTheContractAccepts::complaintsAbout('LogEnvelope', $line))->toBe(
            [],
            sprintf("The payload this suite stands in for a service with is not one a stack would send: line %d.\n", $at),
        );
    }
});
