<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Admitted;
use Modules\Kernel\Api\Admitting;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Sdk\Api\Admissions;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\ADoorThatWasKnockedOn;

// The Admitting contract, run against the adapter and against the fake.
//
// `G2`'s shape, and this port carries the application's only password. Every
// test of a sign-in screen will hand its subject an `ADoorThatWasKnockedOn` and
// never open a socket, so a fake easier to satisfy than the adapter would make
// `N1-R7` green against a door that keeps what it was given.
//
// Both arms are driven from the same table of what the far end did, which is
// what makes the two comparable at all: the adapter is given a response and the
// fake is given the answer that response should produce, and every assertion
// below is written about the `Admitted` they hand back rather than about how
// either got there.
//
// What is deliberately not asserted here: which endpoint is called, what is in
// the body, and that the address was pinned. The fake dials nothing, so a
// contract asking those would either fail on it or be weakened to pass — and a
// weakened contract is how a fake drifts. They are the SDK's own tests, and
// `NothingReachesAStackUnpinnedTest` is what keeps the pinning honest here.

afterEach(function (): void {
    // A global mock outlives the test that set it, and the next file to build a
    // connector would get this one's answer. Torn down here rather than at the
    // end of each case so that a failing assertion cannot skip it.
    MockClient::destroyGlobal();
});

/**
 * A stack to knock on, reachable and pinned so the adapter will build a door.
 *
 * Named for this file: the root suites share one namespace, and two functions
 * of a name are a fatal the moment both load (`G10`).
 */
function aStackWithADoor(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', 64)),
    );
}

/** The session the opening arm carries, in both implementations. */
function theSessionOpened(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** When it stops being one: the stamp below, read as a count of seconds. */
const UNTIL_TEN = 1789380000;

/** What the far end answered, for an exchange that worked. */
function anOpening(): MockResponse
{
    return MockResponse::make(
        '{"api_version":1,"kind":"admission","data":{"token":"a-session-not-a-secret","until":"2026-09-14T10:00:00"}}',
    );
}

/**
 * Both ways of knocking, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument, so a pair returns as an array where the test wanted
 * two parameters.
 *
 * @return array<string, Closure(): Admitting>
 */
function everyDoor(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Admitting => $why instanceof Obstacle
            ? ADoorThatWasKnockedOn::refusing($why)
            : ADoorThatWasKnockedOn::opening(theSessionOpened(), Instant::atEpochSeconds(UNTIL_TEN)),
        'the adapter' => static function () use ($answered): Admitting {
            MockClient::global([$answered]);

            return new Admissions();
        },
    ];
}

/**
 * One word carried out of an `either()` arm.
 *
 * `Admitted::either()` answers with an object, so that a caller cannot pull a
 * session out without saying what happens the other way. A test still wants to
 * compare a string, and this is the smallest honest way across.
 */
final readonly class WhatTheDoorDid
{
    public function __construct(public string $said) {}
}

/** What a door answered, as a word, whichever arm it took. */
function whatHappenedAt(Admitting $door, ?Credential $said = null): string
{
    return $door->admit(aStackWithADoor(), $said ?? Credential::of('the-operators-password'))
        ->either(
            opened: static fn(Session $session, Instant $until): WhatTheDoorDid => new WhatTheDoorDid(
                sprintf('opened %s until %d', $session->forTheHeader(), $until->epochSeconds()),
            ),
            refused: static fn(Obstacle $why): WhatTheDoorDid => new WhatTheDoorDid($why->value),
        )->said;
}

it('comes away with the session the stack opened, and when it ends', function (): void {
    foreach (everyDoor(anOpening()) as $which => $make) {
        expect(whatHappenedAt($make()))
            ->toBe(sprintf('opened a-session-not-a-secret until %d', UNTIL_TEN), $which);
    }
});

it('N1-R10 — tells a refused password from a door that has stopped listening', function (): void {
    // The distinction the whole obstacle set exists for, at the one port where
    // getting it wrong costs the operator something: a wrong password is
    // answered by trying again, and a stack that is not answering is not — and
    // where the door is counting attempts, trying again extends the wait.
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"wait"}', 429), Obstacle::TooManyAttempts],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyDoor($answered, $why) as $which => $make) {
            expect(whatHappenedAt($make()))->toBe($why->value, sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('N1-R7 — spends the credential it was given, so nothing can offer it twice', function (): void {
    // The clause that takes a design rather than care. `Credential` empties
    // itself when it is read, and this is what holds both implementations to
    // reading it: a door that answered without offering would leave every
    // caller holding a password it could send again.
    foreach (everyDoor(anOpening()) as $which => $make) {
        $said = Credential::of('the-operators-password');

        whatHappenedAt($make(), $said);

        expect($said->wasSpent())->toBeTrue($which);
    }
});

it('answers exactly one way, and answers at all', function (): void {
    // What a single-arm assertion above cannot catch. An implementation calling
    // both arms would run a screen's success and its failure, and one calling
    // neither would leave an operator in front of a password they typed and a
    // screen that says nothing.
    foreach (everyDoor(anOpening()) as $which => $make) {
        $arms = 0;
        $count = static function () use (&$arms): WhatTheDoorDid {
            $arms++;

            return new WhatTheDoorDid('counted');
        };

        $make()->admit(aStackWithADoor(), Credential::of('the-operators-password'))
            ->either(opened: $count, refused: $count);

        expect($arms)->toBe(1, $which);
    }
});

it('knocks on the stack it was handed, which is the half a screen cannot check', function (): void {
    // Only the fake can be asked this, and it is asked because every screen test
    // will trust the answer. A screen holding two stacks and offering the
    // password to the wrong one is `N1-R11` broken where it costs most.
    $door = ADoorThatWasKnockedOn::opening(theSessionOpened(), Instant::atEpochSeconds(UNTIL_TEN));
    $stack = aStackWithADoor();

    $door->admit($stack, Credential::of('the-operators-password'));

    expect($door->knockedOn())->toBe($stack)
        ->and($door->knocks())->toBe(1);
});
