<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stalling;
use Modules\Kernel\Api\Stuck;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Stalls;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AStackThatStalled;
use Tests\Support\WhatTheContractAccepts;

// The Stalling contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `WantingContractTest`'s argument one endpoint along: every
// test of the screen listing stalled downloads will hand its subject an
// `AStackThatStalled` and never open a socket, so a fake easier to satisfy than
// the adapter would enforce `N2-R9` against a stack that always answers.
//
// What is deliberately not asserted, as there: which endpoint is called, and
// that the connection was pinned. The fake dials nothing, so a contract asking
// those would either fail on it or be weakened to pass.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose stalled downloads are asked after. */
function aStackWithSomethingStuck(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session it is asked with. */
function theSessionTheStallIsAskedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** What both implementations answer with, where they answer. */
function theSameStalled(): Stalled
{
    return Stalled::of(
        HowMuchIsShown::SomeOfIt,
        Stuck::at('A film nobody has seen', 'radarr', Stage::Searching),
        Stuck::at('A series somebody has', 'sonarr', Stage::Downloaded),
    );
}

/**
 * The payload a stack sends where those two have stopped.
 *
 * Separate from the response so the rule at the foot of this file reads the
 * same array the adapter is given. A fixture checked in one place and sent in
 * another is a fixture that can drift from itself.
 *
 * @return array<string, mixed>
 */
function whatAStackWithSomethingStuckSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'stuck',
        'data' => [
            'incomplete' => true,
            'items' => [
                ['title' => 'A film nobody has seen', 'service' => 'radarr', 'stage' => 'searching'],
                ['title' => 'A series somebody has', 'service' => 'sonarr', 'stage' => 'downloaded'],
            ],
        ],
    ];
}

/** What the far end answers where those two have stopped. */
function aStalledAnswer(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackWithSomethingStuckSends()));
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Closure(): Stalling>
 */
function everyWayOfAskingWhatStopped(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Stalling => $why instanceof Obstacle
            ? AStackThatStalled::met($why)
            : AStackThatStalled::with(theSameStalled()),
        'the adapter' => static function () use ($answered): Stalling {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Stalls(new PinnedClients());
        },
    ];
}

/** One word carried out of an `either()` arm. */
final readonly class WhatTheStallTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** Every stalled row, folded to a word each, so an order can be compared. */
function everythingStuckIn(Stalling $stalling): string
{
    return $stalling->stoppedOn(aStackWithSomethingStuck(), theSessionTheStallIsAskedWith())->either(
        these: static function (Stalled $stalled): WhatTheStallTurnedOutToSay {
            $rows = [];

            foreach ($stalled as $one) {
                $rows[] = $one->stated(
                    static fn(string $title, ServiceId $service, Stage $stage): WhatTheStallTurnedOutToSay
                        => new WhatTheStallTurnedOutToSay(sprintf('%s/%s/%s', $title, $service->named(), $stage->value)),
                )->said;
            }

            return new WhatTheStallTurnedOutToSay(implode(' | ', $rows));
        },
        met: static fn(Obstacle $why): WhatTheStallTurnedOutToSay
            => new WhatTheStallTurnedOutToSay($why->value),
    )->said;
}

/** How much of the listing the answer claims to be, whichever arm it took. */
function howMuchOfTheStallWasShown(Stalling $stalling): string
{
    return $stalling->stoppedOn(aStackWithSomethingStuck(), theSessionTheStallIsAskedWith())->either(
        these: static fn(Stalled $stalled): WhatTheStallTurnedOutToSay
            => new WhatTheStallTurnedOutToSay($stalled->howMuchIsShown()->value),
        met: static fn(Obstacle $why): WhatTheStallTurnedOutToSay
            => new WhatTheStallTurnedOutToSay($why->value),
    )->said;
}

it('N2-R9 — comes away with what stopped, where it stopped, and who has it', function (): void {
    // All three together, in the stack's order. The order is the one the work
    // was queued in, which is how an operator finds the thing that has been
    // wrong longest.
    foreach (everyWayOfAskingWhatStopped(aStalledAnswer()) as $which => $make) {
        expect(everythingStuckIn($make()))->toBe(
            'A film nobody has seen/radarr/searching | A series somebody has/sonarr/downloaded',
            $which,
        );
    }
});

it('says whether the listing is the whole of what the stack holds', function (): void {
    // The field a screen cannot notice the absence of. Both implementations
    // carry it, so a fake that shrugged at `incomplete` could not be used to
    // build a screen that claims to be complete.
    foreach (everyWayOfAskingWhatStopped(aStalledAnswer()) as $which => $make) {
        expect(howMuchOfTheStallWasShown($make()))->toBe(HowMuchIsShown::SomeOfIt->value, $which);
    }
});

it('N1-R10 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingWhatStopped($answered, $why) as $which => $make) {
            expect(everythingStuckIn($make()))->toBe($why->value, $which);
        }
    }
});

it('an answer this app cannot read is a stack that did not answer', function (): void {
    // A row missing its stage is the shape that matters: the adapter refuses
    // it, and the refusal has to reach the screen as an obstacle rather than as
    // a raise. A listing one row short would read as one fewer thing stuck,
    // which is the direction of error nobody goes looking for.
    $answered = MockResponse::make(
        (string) json_encode([
            'api_version' => 1,
            'kind' => 'stuck',
            'data' => [
                'incomplete' => false,
                'items' => [['title' => 'A film nobody has seen', 'service' => 'radarr']],
            ],
        ]),
    );

    foreach (everyWayOfAskingWhatStopped($answered, Obstacle::StackDidNotAnswer) as $which => $make) {
        expect(everythingStuckIn($make()))->toBe(Obstacle::StackDidNotAnswer->value, $which);
    }
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('StuckEnvelope', whatAStackWithSomethingStuckSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
