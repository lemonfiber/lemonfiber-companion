<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\TheirOwn;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\Fakes\AMemberWhoIsOwed;
use Tests\Support\WhatTheContractAccepts;

// The Owing contract, run against the adapter and against the fake.
//
// `G2`'s shape, and the promise is narrow on purpose: the core writes the
// sentences and both implementations hand them over unchanged. A fake that
// composed its own wording would let a screen pass against sentences the core
// would never have written — which is the one failure this port exists to make
// impossible.
//
// What both must also agree on is that a stack declining to say is not a member
// with nothing to be told. They arrive as the same absence and they are
// opposite sentences on a screen.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack a member's own reading is read from. */
function theHouseAMemberBelongsTo(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session a member is holding. */
function theSessionAMemberHolds(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * What the core wrote to the member, which both implementations answer with.
 *
 * Both halves of what a member is owed are here on purpose. The first sentence
 * is whether what they ask for needs approval and the second is what their
 * period has left and when it makes room, so a reader that dropped either would
 * fail one of the two requirements rather than merely shortening a list.
 *
 * @return list<string>
 */
function whatTheCoreWroteToAMember(): array
{
    return [
        'Anything you ask for goes to whoever looks after this house first.',
        'You have two left this month. It makes room again on the 1st.',
    ];
}

/**
 * A household answer narrowed to one member, as the core sends one.
 *
 * Separate from the response so the rule at the foot of this file reads the
 * same array the adapter is given. A fixture checked in one place and sent in
 * another is a fixture that can drift from itself.
 *
 * @param  list<string> $owed
 * @return array<string, mixed>
 */
function whatAStackSendsOneMember(array $owed): array
{
    return [
        'api_version' => 1,
        'kind' => 'household',
        'data' => [
            'available' => true,
            'findings' => [],
            'members' => [[
                'name' => 'Robin',
                'claimed' => true,
                'requests' => [],
                // Written out although nothing here reads it, for the reason
                // the household suite gives: a fixture holding only what its
                // reader wants is a sample of a payload no stack sends.
                'access' => [
                    'administrator' => false,
                    'disabled' => false,
                    'every_library' => true,
                    'libraries' => [],
                    'restriction' => 'unrestricted',
                    'unrated' => 'let-through',
                ],
                'to_hand_over' => $owed,
            ]],
        ],
    ];
}

/**
 * What a stack sends the operator, which is every member of the house.
 *
 * @return array<string, mixed>
 */
function whatAStackSendsAnOperator(): array
{
    return [
        'api_version' => 1,
        'kind' => 'household',
        'data' => ['available' => true, 'findings' => [], 'members' => []],
    ];
}

/**
 * The same sentences, as the port carries them.
 *
 * @param list<string> $said
 */
function theSameSentences(array $said): Sentences
{
    return Sentences::of(...array_map(Sentence::of(...), $said));
}

/**
 * Both ways of asking, each set up to produce the same answer.
 *
 * A plain function rather than a Pest dataset, matching the other contract
 * suites: a dataset whose value is a closure is resolved by Pest and handed
 * back as one argument.
 *
 * @param  list<string> $said
 * @return array<string, Closure(): Owing>
 */
function everyWayOfBeingOwed(MockResponse $answered, array $said, ?Obstacle $why = null): array
{
    return [
        'the fake' => static fn(): Owing => $why instanceof Obstacle
            ? AMemberWhoIsOwed::met($why)
            : AMemberWhoIsOwed::owed(theSameSentences($said)),
        'the adapter' => static function () use ($answered): Owing {
            MockClient::destroyGlobal();
            MockClient::global([$answered]);

            return new TheirOwn(new PinnedClients());
        },
    ];
}

/** One answer carried out of an `either()` arm. */
final readonly class WhatAMemberCameAwayWith
{
    public function __construct(public string $said) {}
}

/** What a stack answered, as one string, whichever arm it took. */
function whatAMemberWasOwed(Owing $owing): string
{
    return $owing->toHandOver(theHouseAMemberBelongsTo(), theSessionAMemberHolds())->either(
        told: static function (Sentences $said): WhatAMemberCameAwayWith {
            $lines = [];

            foreach ($said as $sentence) {
                $lines[] = $sentence->shown();
            }

            return new WhatAMemberCameAwayWith(sprintf('told:%s', implode('|', $lines)));
        },
        refused: static fn(Obstacle $why): WhatAMemberCameAwayWith
            => new WhatAMemberCameAwayWith(sprintf('refused:%s', $why->value)),
    )->said;
}

it('N3-R4 — hands over the sentences the core wrote, unchanged', function (): void {
    // Unchanged is the assertion. Both halves are on the wire in parts — a
    // policy, a standing, two counts, an instant — and an implementation that
    // assembled its own wording from them would be a second voice able to
    // disagree with the core's.
    $said = whatTheCoreWroteToAMember();
    $answered = MockResponse::make((string) json_encode(whatAStackSendsOneMember($said)));

    foreach (everyWayOfBeingOwed($answered, $said) as $which => $build) {
        expect(whatAMemberWasOwed($build()))->toBe(sprintf('told:%s', implode('|', $said)), $which);
    }
});

it('N3-R5 — carries when a spent allowance makes room, because the core says so', function (): void {
    // The reset is a sentence rather than arithmetic here. It is carried by the
    // service that keeps the period, so reading it needs no sum that could be
    // wrong in exactly the cases somebody is waiting on.
    $said = ['Nothing left this month. It makes room again on the 1st.'];
    $answered = MockResponse::make((string) json_encode(whatAStackSendsOneMember($said)));

    foreach (everyWayOfBeingOwed($answered, $said) as $which => $build) {
        expect(whatAMemberWasOwed($build()))->toBe(sprintf('told:%s', $said[0]), $which);
    }
});

it('N3-R3 — tells a stack that would not say from a member with nothing to be told', function (): void {
    // The distinction the whole port turns on. Both arrive as no sentences, and
    // a screen drawing an empty list for the refusal would be passing off *this
    // was not yours to ask* as *there is nothing to tell you*.
    $refused = MockResponse::make('{"error":"no"}', 403);

    foreach (everyWayOfBeingOwed($refused, [], Obstacle::NotForThisAccount) as $which => $build) {
        expect(whatAMemberWasOwed($build()))->toBe('refused:not_for_this_account', $which);
    }

    expect(whatAMemberWasOwed(AMemberWhoIsOwed::owedNothing()))->toBe('told:');
});

it('N1-R10 — says the same about a reading it could not get', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all'), Obstacle::StackDidNotAnswer],
        // A blank line among real ones is something this app cannot show, and
        // the member meets the same thing as an answer that never arrived.
        [
            MockResponse::make((string) json_encode(whatAStackSendsOneMember(['   ']))),
            Obstacle::StackDidNotAnswer,
        ],
    ];

    foreach ($table as [$answered, $why]) {
        foreach (everyWayOfBeingOwed($answered, [], $why) as $which => $build) {
            expect(whatAMemberWasOwed($build()))
                ->toBe(sprintf('refused:%s', $why->value), sprintf('%s, %s', $which, $why->value));
        }
    }
});

it('reads an answer that is not one member\'s as nothing owed to anybody', function (): void {
    // The operator's read of the same endpoint. There is nobody in it to be
    // owed anything, so there is nothing to hand over — which is an answer, and
    // not the refusal above.
    MockClient::destroyGlobal();
    MockClient::global([MockResponse::make((string) json_encode(whatAStackSendsAnOperator()))]);

    expect(whatAMemberWasOwed(new TheirOwn(new PinnedClients())))->toBe('told:');
});

it('N1-R65 — asks the stack it was given, once', function (): void {
    $owing = AMemberWhoIsOwed::owed(theSameSentences(whatTheCoreWroteToAMember()));
    $stack = theHouseAMemberBelongsTo();

    $owing->toHandOver($stack, theSessionAMemberHolds());

    expect($owing->askedAbout())->toBe($stack);
});

it('stands in for a household with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout(
        'HouseholdEnvelope',
        whatAStackSendsOneMember(whatTheCoreWroteToAMember()),
    ))->toBe([], "The payload this suite stands in for a member with is not one a stack would send.\n")
        ->and(WhatTheContractAccepts::complaintsAbout('HouseholdEnvelope', whatAStackSendsAnOperator()))
        ->toBe([], "The payload this suite stands in for an operator with is not one a stack would send.\n");
});
