<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Decided;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Kernel\Api\Wanting;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\Requests;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AHouseholdThatAsked;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

// The Wanting contract, run against the adapter and against the fake.
//
// `G2`'s shape, and `AskingContractTest`'s argument one endpoint along: every
// test of a screen showing requests will hand its subject an
// `AHouseholdThatAsked` and never open a socket, so a fake easier to satisfy
// than the adapter would enforce *a request is approved or refused* against a
// household that always answers.
//
// What is deliberately not asserted, as there: which endpoint is called, and
// that the connection was pinned. The fake dials nothing, so a contract asking
// those would either fail on it or be weakened to pass.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack whose household is asked after. */
function aStackWithAHousehold(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session it is asked with. */
function theSessionTheHouseholdIsAskedWith(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** What both implementations answer with, where they answer. */
function theSameRequests(): Requested
{
    return Requested::of(
        Wanted::of(41, 'Sam', 'A film nobody has seen', Size::guessedAt(4_000_000_000), Waiting::ForApproval),
        Wanted::of(42, 'Robin', 'A series somebody has', Size::unknown(), Waiting::Here),
    );
}

/**
 * The payload a stack sends where the house has asked for those two.
 *
 * Separate from the response so the rule at the foot of this file reads the
 * same array the adapter is given. A fixture checked in one place and sent in
 * another is a fixture that can drift from itself.
 *
 * Every member carries what they may reach, which nothing on this side reads.
 * It is written out anyway, because a fixture holding only what the reader
 * happens to want is a sample of a payload no stack sends — and `access` is the
 * field a screen about what a household may do would be written against next.
 *
 * @return array<string, mixed>
 */
function whatAHouseholdThatAskedSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'household',
        'data' => [
            'available' => true,
            'findings' => [],
            'members' => [
                [
                    'name' => 'Sam',
                    'access' => whatAMemberMayReach(),
                    'claimed' => true,
                    'to_hand_over' => [],
                    'requests' => [[
                        'id' => 41,
                        'title' => 'A film nobody has seen',
                        'state' => 'waiting-for-approval',
                        'estimate' => ['bytes' => 4_000_000_000, 'measured' => false],
                    ]],
                ],
                [
                    'name' => 'Robin',
                    'access' => whatAMemberMayReach(everyLibrary: false),
                    'claimed' => true,
                    'to_hand_over' => [],
                    // No estimate at all, which the contract permits and
                    // which is answered with *we do not know* rather
                    // than a guess of nothing.
                    'requests' => [[
                        'id' => 42,
                        'title' => 'A series somebody has',
                        'state' => 'here',
                    ]],
                ],
            ],
        ],
    ];
}

/**
 * What one member may reach, as a stack states it.
 *
 * Both members carry one rather than sharing it, because the two answers differ
 * and a fixture where every member may reach everything would not tell a reader
 * written later that they can differ at all.
 *
 * @return array<string, mixed>
 */
function whatAMemberMayReach(bool $everyLibrary = true): array
{
    return [
        'administrator' => false,
        'disabled' => false,
        'every_library' => $everyLibrary,
        'libraries' => $everyLibrary ? [] : ['films'],
        'restriction' => $everyLibrary ? 'unrestricted' : 'library-limited',
        'unrated' => 'let-through',
    ];
}

/** What the far end answers where the house has asked for those two. */
function aHouseholdAnswer(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAHouseholdThatAskedSends()));
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @return array<string, Closure(): Wanting>
 */
function everyWayOfAskingTheHousehold(MockResponse $answered, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Wanting => $why instanceof Obstacle
            ? AHouseholdThatAsked::met($why)
            : AHouseholdThatAsked::wanting(theSameRequests()),
        'the adapter' => static function () use ($answered): Wanting {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new Requests(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/**
 * What a stack answers when it takes a decision on.
 *
 * @return array<string, mixed>
 */
function whatAStackTakingADecisionSends(): array
{
    return [
        'api_version' => 1,
        'kind' => 'job',
        'data' => ['action' => 'household-approve', 'job' => AHouseholdThatAsked::THE_JOB],
    ];
}

/** What the far end answers when a decision reaches it. */
function aDecidedAnswer(): MockResponse
{
    return MockResponse::make((string) json_encode(whatAStackTakingADecisionSends()));
}

/** What came of telling a stack what was decided, as a word. */
function whatCameOfDeciding(Wanting $wanting, Decided $decided): string
{
    return $wanting->decided(aStackWithAHousehold(), theSessionTheHouseholdIsAskedWith(), $decided)->either(
        started: static fn(Job $job): WhatTheHouseholdTurnedOutToSay
            => new WhatTheHouseholdTurnedOutToSay($job->shown()),
        met: static fn(Obstacle $why): WhatTheHouseholdTurnedOutToSay
            => new WhatTheHouseholdTurnedOutToSay($why->value),
    )->said;
}

it('N2-R11 — takes an approval and comes away with a name to ask about', function (): void {
    $decided = Decided::toApprove(RequestId::numbered(41));

    foreach (everyWayOfAskingTheHousehold(aDecidedAnswer()) as $which => $make) {
        expect(whatCameOfDeciding($make(), $decided))->toBe(AHouseholdThatAsked::THE_JOB, $which);
    }
});

it('D7-R7 — takes a refusal, which carries the sentence it owes', function (): void {
    // The other of the two decisions, and the one that carries something extra. A
    // port taking only the approval would have a screen turning a request down
    // by leaving it alone, which is exactly what may not happen.
    $decided = Decided::toDecline(RequestId::numbered(41), 'No room this month');

    foreach (everyWayOfAskingTheHousehold(aDecidedAnswer()) as $which => $make) {
        expect(whatCameOfDeciding($make(), $decided))->toBe(AHouseholdThatAsked::THE_JOB, $which);
    }
});

it('N1-R10 — says the same about a decision it could not deliver', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $met]) {
        foreach (everyWayOfAskingTheHousehold($answered, $met) as $which => $make) {
            expect(whatCameOfDeciding($make(), Decided::toApprove(RequestId::numbered(41))))
                ->toBe($met->value, $which);
        }
    }
});

/**
 * What the adapter actually put on the wire for a decision.
 *
 * Asserted here and nowhere else in this suite, because it is the one call
 * whose *body* carries a decision: a reading asks for nothing, and a verb names
 * its subject in a list the other contract suite reads. A decision sent with
 * the wrong number is one made about somebody else's request, and nothing on
 * the way back would say so — the stack answers with a job either way.
 *
 * @return array<string, mixed>
 */
function whatTheWireCarriedForADecision(Decided $decided): array
{
    MockClient::destroyGlobal();
    MockClient::global([aDecidedAnswer()]);

    new Requests(new PinnedClients(), SequencedEntropy::counting())
        ->decided(aStackWithAHousehold(), theSessionTheHouseholdIsAskedWith(), $decided);

    $sent = MockClient::getGlobal()?->getLastPendingRequest()?->body()?->all();

    if (! is_array($sent)) {
        // Raised rather than answered with `[]`. A decision that never reached
        // the wire would otherwise read as a body with the wrong fields in it,
        // and the fault named would be the wrong one.
        throw new RuntimeException('No decision reached the wire.');
    }

    // A body carrying a numeric key is a list where the endpoint reads a map,
    // which the equality check reports as a wrong value rather than as the
    // wrong shape.
    expect(array_keys($sent))->each->toBeString();

    /** @var array<string, mixed> $sent */
    return $sent;
}

it('N2-R11 — an approval names the request and nothing else', function (): void {
    expect(whatTheWireCarriedForADecision(Decided::toApprove(RequestId::numbered(41))))
        ->toBe(['request' => 41]);
});

it('D7-R7 — a refusal names the request and carries the sentence with it', function (): void {
    // Both keys, and the number among them: a refusal sent without its reason
    // is the wire half of a refusal that says nothing, and one sent with the
    // wrong number turns somebody else's request down.
    expect(whatTheWireCarriedForADecision(Decided::toDecline(RequestId::numbered(41), 'No room this month')))
        ->toBe(['request' => 41, 'reason' => 'No room this month']);
});

/** One word carried out of an `either()` arm. */
final readonly class WhatTheHouseholdTurnedOutToSay
{
    public function __construct(public string $said) {}
}

/** What a household answered, as a word, whichever arm it took. */
function whatTheHouseholdSaid(Wanting $wanting): string
{
    return $wanting->askedOf(aStackWithAHousehold(), theSessionTheHouseholdIsAskedWith())->either(
        these: static fn(Requested $wanted): WhatTheHouseholdTurnedOutToSay
            => new WhatTheHouseholdTurnedOutToSay(sprintf('%d of which %d waiting', $wanted->count(), $wanted->waiting())),
        met: static fn(Obstacle $why): WhatTheHouseholdTurnedOutToSay
            => new WhatTheHouseholdTurnedOutToSay($why->value),
    )->said;
}

/** Every request, folded to a word each, so an order can be compared. */
function everyRequestIn(Wanting $wanting): string
{
    return $wanting->askedOf(aStackWithAHousehold(), theSessionTheHouseholdIsAskedWith())->either(
        these: static function (Requested $wanted): WhatTheHouseholdTurnedOutToSay {
            $rows = [];

            foreach ($wanted as $one) {
                $rows[] = sprintf('%d/%s/%s/%s', $one->number(), $one->by(), $one->forWhat(), $one->standing()->value);
            }

            return new WhatTheHouseholdTurnedOutToSay(implode(' | ', $rows));
        },
        met: static fn(Obstacle $why): WhatTheHouseholdTurnedOutToSay
            => new WhatTheHouseholdTurnedOutToSay($why->value),
    )->said;
}

it('N2-R11 — comes away with what the house asked for, and how much wants deciding', function (): void {
    foreach (everyWayOfAskingTheHousehold(aHouseholdAnswer()) as $which => $make) {
        expect(whatTheHouseholdSaid($make()))->toBe('2 of which 1 waiting', $which);
    }
});

it('D7-R7 — carries who asked, onto every row, in the order the stack listed them', function (): void {
    // The requester is on the row rather than on a heading, because a decline
    // has to reach them by name — and the order is the stack's, which is the
    // order the house asked in and how somebody finds theirs.
    foreach (everyWayOfAskingTheHousehold(aHouseholdAnswer()) as $which => $make) {
        expect(everyRequestIn($make()))->toBe(
            '41/Sam/A film nobody has seen/waiting-for-approval | 42/Robin/A series somebody has/here',
            $which,
        );
    }
});

it('N1-R10 — tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfAskingTheHousehold($answered, $why) as $which => $make) {
            expect(whatTheHouseholdSaid($make()))->toBe($why->value, sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('answers exactly one way, and answers at all', function (): void {
    foreach (everyWayOfAskingTheHousehold(aHouseholdAnswer()) as $which => $make) {
        $arms = 0;
        $count = static function () use (&$arms): WhatTheHouseholdTurnedOutToSay {
            $arms++;

            return new WhatTheHouseholdTurnedOutToSay('counted');
        };

        $make()
            ->askedOf(aStackWithAHousehold(), theSessionTheHouseholdIsAskedWith())
            ->either(these: $count, met: $count);

        expect($arms)->toBe(1, $which);
    }
});

it('N1-R65 — asks once, because a frame reads a machine once', function (): void {
    $wanting = AHouseholdThatAsked::wanting(theSameRequests());
    $stack = aStackWithAHousehold();

    $wanting->askedOf($stack, theSessionTheHouseholdIsAskedWith());

    expect($wanting->askedAbout())->toBe($stack)
        ->and($wanting->askings())->toBe(1)
        ->and($wanting->wasGivenASession())->toBeTrue();
});

it('stands in for a household with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('HouseholdEnvelope', whatAHouseholdThatAskedSends()))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
