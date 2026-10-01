<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\AConnection;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAConnectionEnded;
use Modules\Kernel\Api\HowDriftWasJudged;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatItWouldBreak;
use Modules\Kernel\Api\WiringTheServices;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Wirers;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatWires;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

// The WiringTheServices contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `InvitingContractTest`'s argument one act along: the run
// answers with a handle, and asking after the handle answers with the run.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose services are wired. */
function aStackToWire(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** What both implementations answer with once the run is done. */
function theSameRun(): TheWiring
{
    return TheWiring::written(
        HowDriftWasJudged::Assessed,
        WhatIsUnsupported::none(),
        AConnection::of('SABnzbd into Sonarr', WhatItWouldBreak::warning('Downloads never arrive', 'Point it again'), HowAConnectionEnded::failed('401 Unauthorized')),
    );
}

/**
 * What the run carries, as a stack sends it.
 *
 * @return array<string, mixed>
 */
function whatTheRunCarries(): array
{
    return [
        'assessment' => 'assessed',
        'rehearsed' => false,
        'wirings' => [[
            'connection' => 'SABnzbd into Sonarr',
            'severity' => ['severity' => 'warning', 'breakage' => 'Downloads never arrive', 'remediation' => 'Point it again'],
            'state' => ['state' => 'failed', 'detail' => '401 Unauthorized'],
        ]],
    ];
}

/**
 * The payload a stack sends for that, carrying whatever run it is given.
 *
 * @param array<string, mixed> $run
 * @return array<string, mixed>
 */
function whatAStackSaysOfTheRun(array $run = []): array
{
    return ['api_version' => 1, 'kind' => 'seed', 'data' => $run === [] ? whatTheRunCarries() : $run];
}

/** The handle a stack answers a run with. */
function theHandleARunIsAnsweredWith(): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'seed']]), 202);
}

/**
 * Both ways of wiring, each set up to answer the handle and then the run.
 *
 * @return array<string, Closure(): WiringTheServices>
 */
function everyWayOfWiring(MockResponse ...$answered): array
{
    return [
        'the fake' => static fn(): WiringTheServices => AStackThatWires::answering(
            WhatBecameOfTheWiring::underway(Job::named('j-1')),
            WhatBecameOfTheWiring::answered(theSameRun()),
        ),
        'the adapter' => static function () use ($answered): WiringTheServices {
            MockClient::destroyGlobal();
            MockClient::global($answered);

            return new Wirers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** One line carried out of an `either()` arm. */
final readonly class WhatTheWiringTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Everything an answer to wiring says, folded to one line, so two answers can be compared. */
function everythingTheWiringSays(WhatBecameOfTheWiring $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhatTheWiringTurnedOutToSay => new WhatTheWiringTurnedOutToSay(sprintf('underway %s', $job->shown())),
        answered: static function (TheWiring $wiring): WhatTheWiringTurnedOutToSay {
            $said = [$wiring->judged()->value, $wiring->wasRehearsed() ? 'rehearsed' : 'written'];

            foreach ($wiring as $connection) {
                $said[] = sprintf('%s %s %s %s', $connection->connection(), $connection->ended()->state()->value, $connection->ended()->said(), $connection->breaks()->breakage());
            }

            return new WhatTheWiringTurnedOutToSay(implode('|', $said));
        },
        ended: static fn(): WhatTheWiringTurnedOutToSay => new WhatTheWiringTurnedOutToSay('ended'),
        refused: static fn(string $because): WhatTheWiringTurnedOutToSay => new WhatTheWiringTurnedOutToSay(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): WhatTheWiringTurnedOutToSay => new WhatTheWiringTurnedOutToSay($why->kind()->value),
    )->said;
}

it('starts a run and follows the work to what it came to', function (): void {
    $done = MockResponse::make((string) json_encode(whatAStackSaysOfTheRun()));

    foreach (everyWayOfWiring(theHandleARunIsAnsweredWith(), $done) as $which => $make) {
        $wiring = $make();
        $started = $wiring->wire(aStackToWire(), Session::of('a-session-not-a-secret'));
        $finished = $wiring->whatBecameOf(aStackToWire(), Session::of('a-session-not-a-secret'), Job::named('j-1'));

        expect(everythingTheWiringSays($started))->toBe('underway j-1', $which)
            ->and(everythingTheWiringSays($finished))->toBe('assessed|written|SABnzbd into Sonarr failed 401 Unauthorized Downloads never arrive', $which);
    }
});

it('asks for the run by lemonfiber\'s name for it, with nothing but a key', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([theHandleARunIsAnsweredWith()]);

    new Wirers(new PinnedClients(), SequencedEntropy::counting())->wire(aStackToWire(), Session::of('a-session-not-a-secret'));
    $asked = $mock->getLastPendingRequest();

    expect($asked?->getUrl())->toEndWith('/api/actions/seed')
        ->and($asked?->body()?->all())->toBe([])
        ->and($asked?->headers()->get(Api::IDEMPOTENCY_HEADER))->not->toBeNull();
});

it('keeps following a run the stack is still carrying out', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([theHandleARunIsAnsweredWith()]);

    expect(everythingTheWiringSays(new Wirers(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(aStackToWire(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))
        ->toBe('underway j-1');
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('Not yours to ask', 403, ['Content-Type' => 'text/plain']), Obstacle::of(KindOfObstacle::NotForThisAccount)],
        [MockResponse::make('The machine failed', 500, ['Content-Type' => 'text/plain']), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all', 202), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        $ways = [
            'the fake' => static fn(): WiringTheServices => AStackThatWires::met($why),
            'the adapter' => everyWayOfWiring($answered)['the adapter'],
        ];

        foreach ($ways as $which => $make) {
            expect(everythingTheWiringSays($make()->wire(aStackToWire(), Session::of('a-session-not-a-secret'))))
                ->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('hands on a refusal in the stack\'s own words', function (int $status): void {
    $refused = MockResponse::make('Nothing here to wire', $status, ['Content-Type' => 'text/plain']);
    $ways = [
        'the fake' => static fn(): WiringTheServices => AStackThatWires::answering(WhatBecameOfTheWiring::refused('Nothing here to wire')),
        'the adapter' => everyWayOfWiring($refused)['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        expect(everythingTheWiringSays($make()->wire(aStackToWire(), Session::of('a-session-not-a-secret'))))
            ->toBe('refused Nothing here to wire', $which);
    }
})->with([400, 422, 499]);

it('hands on a refusal met while following the run, too', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make('That name was never handed out', 400, ['Content-Type' => 'text/plain'])]);

    expect(everythingTheWiringSays(new Wirers(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(aStackToWire(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))
        ->toBe('refused That name was never handed out');
});

it('says the stack has no outcome for a run it no longer knows', function (): void {
    $ways = [
        'the fake' => static fn(): WiringTheServices => AStackThatWires::answering(),
        'the adapter' => everyWayOfWiring(MockResponse::make('{"error":"no such job"}', 404))['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        expect(everythingTheWiringSays($make()->whatBecameOf(aStackToWire(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))->toBe('ended', $which);
    }
});

it('a run this app cannot read is an obstacle, never a run that did less', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSaysOfTheRun([...whatTheRunCarries(), 'assessment' => 'mostly'])))]);

    expect(everythingTheWiringSays(new Wirers(new PinnedClients(), SequencedEntropy::counting())->whatBecameOf(aStackToWire(), Session::of('a-session-not-a-secret'), Job::named('j-1'))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->value);
});

it('a handle this app cannot read is an obstacle, never a run under way', function (): void {
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => ' ', 'action' => 'seed']]), 202)]);

    expect(everythingTheWiringSays(new Wirers(new PinnedClients(), SequencedEntropy::counting())->wire(aStackToWire(), Session::of('a-session-not-a-secret'))))
        ->toEqual(KindOfObstacle::StackDidNotAnswer->value);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('SeedEnvelope', whatAStackSaysOfTheRun()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
