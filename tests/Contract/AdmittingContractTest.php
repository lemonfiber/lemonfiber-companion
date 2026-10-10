<?php

declare(strict_types=1);

use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\Unreachable;
use Modules\Kernel\Api\AClaim;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Admitting;
use Modules\Kernel\Api\AJoinLink;
use Modules\Kernel\Api\AMembersName;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Api\Admissions;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\PinnedDoors;
use Saloon\Contracts\Body\BodyRepository;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\ADoorThatWasKnockedOn;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The Admitting contract, run against the adapter and against the fake.
//
// `G2`'s shape, and this port carries the application's only password. Every
// test of a sign-in screen will hand its subject an `ADoorThatWasKnockedOn` and
// never open a socket, so a fake easier to satisfy than the adapter would make
// *the password is exchanged once* green against a door that keeps what it was
// given.
//
// Both arms are driven from the same table of what the far end did, which is
// what makes the two comparable at all: the adapter is given a response and the
// fake is given the answer that response should produce, and every assertion
// below is written about the `Admitted` they hand back rather than about how
// either got there.
//
// **This is the one place in the application that names the SDK's transport,
// and it is a test.** `saloonphp/saloon` is a dev dependency for this file
// alone: driving the adapter means scripting what the far end said, and the far
// end is reached through Saloon. Declared rather than used transitively, which
// the deps gate asks for and is right to — a package used and not named is one
// that disappears the day the SDK swaps its client.
//
// It does not weaken the rule that nothing but the SDK reaches a stack.
// Nothing in `app-modules/` or `bootstrap/` may name it,
// `NothingReachesAStackUnpinnedTest` is what refuses that, and a test
// that could not script an answer would be a test asserting the fake against
// itself.
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

/**
 * The payload a door sends where the exchange worked.
 *
 * Separate from the response, and an array rather than the text it goes as, so
 * the rule at the foot of this file reads the same payload the adapter is
 * given. Hand-written text is the one shape a fixture can be wrong in without
 * anything reading it — a misspelled key in a string is a key, and the reader
 * that agreed with the misspelling would pass.
 *
 * @return array<string, mixed>
 */
function whatADoorThatOpenedSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'admission',
        'data' => ['token' => 'a-session-not-a-secret', 'until' => '2026-09-14T10:00:00'],
    ];
}

/** What the far end answered, for an exchange that worked. */
function anOpening(): MockResponse
{
    return MockResponse::make((string) json_encode(whatADoorThatOpenedSends()));
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
            // Destroyed first, because `global()` does **not** replace a global
            // mock that is already set — it leaves the one that is there, whose
            // single response the previous case has already spent. A test that
            // drives a table of answers then passes on its first row and fails
            // on the rest with "Saloon was unable to guess a mock response",
            // which reads like the adapter calling an address nobody mocked.
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Admissions(new PinnedDoors(), new PinnedClients());
        },
    ];
}

/** What a door answered, as a word, whichever arm it took. */
function whatHappenedAt(Admitting $door, ?Credential $said = null): string
{
    return $door->admit(aStackWithADoor(), $said ?? Credential::of('the-operators-password'))
        ->either(
            opened: static fn(Session $session, Instant $until): TheWordCarriedOut => new TheWordCarriedOut(
                sprintf('opened %s until %d', $session->forTheHeader(), $until->epochSeconds()),
            ),
            refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
        )->said;
}

it('comes away with the session the stack opened, and when it ends', function (): void {
    foreach (everyDoor(anOpening()) as $which => $make) {
        expect(whatHappenedAt($make()))
            ->toBe(sprintf('opened a-session-not-a-secret until %d', UNTIL_TEN), $which);
    }
});

it('tells a refused password from a door that has stopped listening', function (): void {
    // The distinction the whole obstacle set exists for, at the one port where
    // getting it wrong costs the operator something: a wrong password is
    // answered by trying again, and a stack that is not answering is not — and
    // where the door is counting attempts, trying again extends the wait.
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"wait"}', 429), Obstacle::of(KindOfObstacle::TooManyAttempts)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make()->throw(static fn(): Unreachable
            => Unreachable::whenAsking('/api/session', 'Connection refused')), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make()->throw(static fn(): CertificateWasRefused
            => CertificateWasRefused::whenAsking('/api/session', str_repeat('c', 64), str_repeat('b', 64))), Obstacle::of(KindOfObstacle::StackIsNotTheOnePaired)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyDoor($answered, $why) as $which => $make) {
            expect(whatHappenedAt($make()))->toBe($why->kind()->value, sprintf('%s, %s', $which, $why->kind()->value));
        }
    }
});

it('spends the credential it was given, so nothing can offer it twice', function (): void {
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
        $count = static function () use (&$arms): TheWordCarriedOut {
            $arms++;

            return new TheWordCarriedOut('counted');
        };

        $make()->admit(aStackWithADoor(), Credential::of('the-operators-password'))
            ->either(opened: $count, refused: $count);

        expect($arms)->toBe(1, $which);
    }
});

it('knocks on the stack it was handed, which is the half a screen cannot check', function (): void {
    // Only the fake can be asked this, and it is asked because every screen test
    // will trust the answer. A screen holding two stacks and offering the
    // password to the wrong one is two machines mistaken for each other, where
    // it costs most.
    $door = ADoorThatWasKnockedOn::opening(theSessionOpened(), Instant::atEpochSeconds(UNTIL_TEN));
    $stack = aStackWithADoor();

    $door->admit($stack, Credential::of('the-operators-password'));

    expect($door->knockedOn())->toBe($stack)
        ->and($door->knocks())->toBe(1);
});

it('offers a member\'s name beside the credential, and comes away with whose session the stack opened', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([MockResponse::make((string) json_encode([
        'api_version' => 1,
        'kind' => 'admission',
        'data' => ['member' => 'a7f3', 'token' => 'a-session-not-a-secret', 'until' => '2026-09-14T10:00:00'],
    ]))]);
    $whose = new Admissions(new PinnedDoors(), new PinnedClients())
        ->admitAs(aStackWithADoor(), AMembersName::of('ada'), Credential::of('a-members-password'))
        ->either(
            opened: static fn(Session $session, Instant $until, Whose $whose): TheWordCarriedOut => $whose->either(
                operator: static fn(): TheWordCarriedOut => new TheWordCarriedOut('the operator'),
                member: static fn(string $id): TheWordCarriedOut => new TheWordCarriedOut($id),
            ),
            refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
        )->said;
    $body = $mock->getLastPendingRequest()?->body();

    expect($whose)->toBe('a7f3')
        ->and($body instanceof BodyRepository ? $body->all() : [])->toBe(['name' => 'ada', 'password' => 'a-members-password']);
});

it('tells a name and password the stack did not recognise from a door that has stopped listening, as a member', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyDoor($answered, $why) as $which => $make) {
            $said = $make()->admitAs(aStackWithADoor(), AMembersName::of('ada'), Credential::of('a-members-password'))->either(
                opened: static fn(): TheWordCarriedOut => new TheWordCarriedOut('opened'),
                refused: static fn(Obstacle $refused): TheWordCarriedOut => new TheWordCarriedOut($refused->kind()->value),
            )->said;

            expect($said)->toBe($why->kind()->value, sprintf('%s, %s', $which, $why->kind()->value));
        }
    }
});

/** A refusal the door answers a claim with, carrying its code and the house's words. */
function aClaimRefusedUnder(string $code, int $status): MockResponse
{
    return MockResponse::make((string) json_encode([
        'api_version' => 1,
        'kind' => 'error',
        'data' => ['code' => $code, 'summary' => 'Refused at the door.', 'meaning' => 'm', 'severity' => 'error', 'state' => 'actionable', 'remedies' => []],
    ]), $status);
}

/** What claiming came to at a door, as a word, whichever arm it took. */
function whatClaimingCameTo(Admitting $door, ?Credential $chosen = null): string
{
    $claim = AJoinLink::read(
        sprintf('lemonfiber://join?address=%s&fingerprint=%s&stack=%s&expires=2000&name=ada&claim=a-claim-token', rawurlencode('https://192.168.1.42:8443'), str_repeat('b', 64), str_repeat('a', 32)),
        FrozenClock::at(Instant::atEpochSeconds(1000)),
    )->leadsTo(claiming: static fn(AClaim $carried): AClaim => $carried, signingIn: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no claim'));

    return $claim instanceof AClaim
        ? $door->claimAs(aStackWithADoor(), AMembersName::of('ada'), $chosen ?? Credential::of('a-password-of-twelve'), $claim)->either(
            opened: static fn(Session $session): TheWordCarriedOut => new TheWordCarriedOut(sprintf('opened %s', $session->forTheHeader())),
            refused: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
        )->said
        : 'no claim';
}

it('claims an invitation with the name, the chosen password and the claim, and comes away with the member\'s session', function (): void {
    MockClient::destroyGlobal();
    $mock = MockClient::global([anOpening()]);
    $chosen = Credential::of('a-password-of-twelve');

    $said = whatClaimingCameTo(new Admissions(new PinnedDoors(), new PinnedClients()), $chosen);
    $body = $mock->getLastPendingRequest()?->body();

    expect($said)->toBe('opened a-session-not-a-secret')
        ->and($chosen->wasSpent())->toBeTrue()
        ->and($body instanceof BodyRepository ? $body->all() : [])->toBe(['name' => 'ada', 'password' => 'a-password-of-twelve', 'claim' => 'a-claim-token']);
});

it('reads an invitation no longer open, a choice too short and a media server that could not confirm from their codes, never as a wrong password', function (): void {
    $table = [
        [aClaimRefusedUnder('ADMIT-13', 401), KindOfObstacle::InvitationNotOpen],
        [aClaimRefusedUnder('ADMIT-14', 400), KindOfObstacle::ChosenPasswordTooShort],
        [aClaimRefusedUnder('ADMIT-7', 403), KindOfObstacle::MediaServerDidNotAnswer],
        [MockResponse::make('{"error":"wait"}', 429), KindOfObstacle::TooManyAttempts],
    ];

    foreach ($table as [$answered, $kind]) {
        MockClient::destroyGlobal();
        MockClient::global([$answered]);

        expect(whatClaimingCameTo(new Admissions(new PinnedDoors(), new PinnedClients())))->toBe($kind->value, $kind->value);
    }
});

it('stands in for a door with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('AdmissionEnvelope', whatADoorThatOpenedSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
