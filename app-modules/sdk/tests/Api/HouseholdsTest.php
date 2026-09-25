<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;
use function iterator_to_array;
use function json_encode;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Sdk\Api\HouseholdIsUnreadable;
use Modules\Sdk\Api\Households;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `household` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see doctorSaying()}'s reason: what
 * is under test is what happens when the wire says something the contract does
 * not allow, which a client honouring the contract could never produce.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function householdSaying(array $data): Envelope
{
    return new Envelope(1, 'household', $data);
}

/**
 * What one member may reach, which every member on the wire carries.
 *
 * Nothing here reads it. It is carried because a fixture short of a field the
 * contract requires is a sample of a payload no stack sends, and a reader
 * tested only against that has been tested against nothing.
 *
 * @return array<string, mixed>
 */
function whatOneMemberMayReach(): array
{
    return [
        'administrator' => false,
        'disabled' => false,
        'every_library' => true,
        'libraries' => [],
        'restriction' => 'unrestricted',
        'unrated' => 'let-through',
    ];
}

/**
 * One member, complete, with the requests they made.
 *
 * @param  list<mixed> $requests
 * @return array<string, mixed>
 */
function aMember(string $name, array $requests): array
{
    return [
        'name' => $name,
        'access' => whatOneMemberMayReach(),
        'claimed' => true,
        'to_hand_over' => [],
        'requests' => $requests,
    ];
}

/**
 * A whole house, around whatever members the case in hand is about.
 *
 * @param  list<mixed> $members
 * @return array<string, mixed>
 */
function aHouseholdOf(array $members): array
{
    return ['available' => true, 'findings' => [], 'members' => $members];
}

/**
 * One request, with every part the reader insists on.
 *
 * @return array<string, mixed>
 */
function aRequest(int $id, string $title, string $state): array
{
    return ['id' => $id, 'title' => $title, 'state' => $state];
}

it('reads a house into the flat list the screens work in', function (): void {
    $data = aHouseholdOf([
        aMember('Robin', [aRequest(1, 'A film', 'waiting-for-approval')]),
        aMember('Sam', [aRequest(2, 'A season', 'getting')]),
    ]);

    $wanted = iterator_to_array(Households::in(householdSaying($data)), preserve_keys: false);

    // Two people, two requests, one list — and each row still knows who asked,
    // which is the fact the flattening exists to keep.
    expect($wanted)->toHaveCount(2)
        ->and($wanted[0]->by())->toBe('Robin')
        ->and($wanted[1]->by())->toBe('Sam')
        ->and(theStandingRead($wanted[1]))->toBe('getting');
});

it('refuses a payload that is not an object at all', function (): void {
    // The generated envelope asserts its shape without checking it, which is
    // why the payload is read as `mixed` in the first place: an assertion is a
    // claim about the contract, not a fact about the socket.
    expect(fn(): Requested => Households::in(new Envelope(1, 'household', 'sorry')))
        ->toThrow(HouseholdIsUnreadable::class, 'data');
});

it("refuses a payload that is not an object at all when reading one member's own", function (): void {
    // The member's entry point reads the same socket as the operator's and owes
    // the same refusal. A second entry point that salvaged where the first
    // refused would be the one place a member is shown something an operator
    // would have been told was unreadable.
    expect(fn(): Requested => Households::theirOwnIn(new Envelope(1, 'household', 'sorry')))
        ->toThrow(HouseholdIsUnreadable::class, 'data');
});

it('N3-R3 — refuses a household the stack says it could not read, rather than an empty one', function (): void {
    // The field exists for this and the contract says so: a false `available`
    // is *why* the list is empty. Reading the empty list instead would tell an
    // operator there is nothing to decide, and a member that they have asked
    // for nothing, when the truth is that nobody could find out.
    //
    // Both entry points, because both would otherwise draw the same empty list
    // from the same payload.
    $unread = householdSaying(['available' => false, 'findings' => [], 'members' => []]);

    expect(fn(): Requested => Households::in($unread))
        ->toThrow(HouseholdIsUnreadable::class, 'could not read the household')
        ->and(fn(): Requested => Households::theirOwnIn($unread))
        ->toThrow(HouseholdIsUnreadable::class, 'could not read the household');
});

it('reads a household the stack could read as the answer it is', function (): void {
    // The other side of the guard, so it cannot pass by refusing everything: a
    // house that read cleanly and holds nobody is an empty house, and an empty
    // house is an answer.
    expect(Households::in(householdSaying(['available' => true, 'findings' => [], 'members' => []])))
        ->toHaveCount(0);
});

it('refuses a house with no members key rather than inventing an empty one', function (): void {
    // Absent is not empty. A house whose members were lost in transit and a
    // house where nobody has asked for anything are different facts, and only
    // the second is a quiet week.
    expect(fn(): Requested => Households::in(householdSaying([])))
        ->toThrow(HouseholdIsUnreadable::class, 'members');
});

it('refuses members that are not a list at all', function (): void {
    expect(fn(): Requested => Households::in(householdSaying(['members' => 'nobody'])))
        ->toThrow(HouseholdIsUnreadable::class, 'members');
});

it('refuses a member row it cannot read, and says which one', function (): void {
    // A member this app cannot read takes everything that person asked for
    // with them, so the refusal names the row rather than showing the house one
    // person short.
    $data = ['members' => [aMember('Robin', []), 'not a member']];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'Member 1');
});

it('refuses a member with no name rather than showing requests nobody owns', function (): void {
    $data = ['members' => [['requests' => []]]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'name');
});

it('refuses a name that is there and is not text', function (): void {
    $data = ['members' => [['name' => 7, 'requests' => []]]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'name');
});

it('refuses a member whose requests it cannot find, and names the member not the envelope', function (): void {
    // The sentence matters as much as the refusal. Read through `rows()` this
    // said *the household envelope has no `requests`* — true of an envelope and
    // false of a member, so a house whose second member lost their requests key
    // reported as an answer from a lemonfiber this app cannot read, and dropped
    // the one fact that would make it findable.
    //
    // The second member rather than the first, so the position has to be
    // carried to be right: `Member 0` would pass this by accident.
    $data = ['members' => [aMember('Robin', []), ['name' => 'Sam']]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'Member 1');
});

it('refuses a member whose requests are not a list, for the reason a missing key is refused', function (): void {
    // Absent and unreadable are one answer here, unlike a size: either way
    // nothing can be said about what this person asked for, and a member shown
    // with no requests is a member shown as having wanted nothing.
    $data = ['members' => [aMember('Robin', []), ['name' => 'Sam', 'requests' => 'none']]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'Member 1');
});

it('refuses a request row it cannot read, naming who asked and where', function (): void {
    // The sharp one. A list one request short reads as somebody never having
    // asked — while the person who asked is in the house and will ask again, of
    // an operator who has been shown a screen saying there is nothing to decide.
    $data = ['members' => [aMember('Robin', ['not a request'])]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'Request 0');
});

it('refuses a request with no id rather than one nothing can be decided about', function (): void {
    $data = ['members' => [aMember('Robin', [['title' => 'A film', 'state' => 'getting']])]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'Request 0');
});

it('refuses a request with no title, which the contract permits and a screen cannot show', function (): void {
    // Permitted to be absent and still refused: a row with no title is a row
    // nobody can decide on, and the refusal happens here while the member and
    // the position are still in hand.
    $data = ['members' => [aMember('Robin', [['id' => 1, 'state' => 'getting']])]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'Robin');
});

it('reads a request with no state, which is not the same absence as no title', function (): void {
    // This used to refuse, *for the same reason as one with no title*, and the
    // two are not the same reason at all. A row with no title is a row nobody
    // can decide on — the contract permits the absence and says nothing about
    // what it means. A row with no state is the contract saying something
    // precise: the request service reported a status lemonfiber has no word
    // for, and it was left out rather than guessed into the nearest one.
    //
    // Treating the second like the first cost the whole reading, for one row.
    $data = ['members' => [aMember('Robin', [['id' => 1, 'title' => 'A film']])]];

    $wanted = iterator_to_array(Households::in(householdSaying($data)), preserve_keys: false);

    expect($wanted)->toHaveCount(1)
        ->and(theStandingRead($wanted[0]))->toBe('nobody named it');
});

it('refuses a state word it does not know, and names the ones it reads', function (): void {
    // Guessing which was meant is how something already in the house gets
    // offered for approval. The accepted list comes from the enum, so a case
    // added to the contract cannot leave the message describing the old one.
    $data = ['members' => [aMember('Robin', [aRequest(1, 'A film', 'pondering')])]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'pondering');
});

it('D7-R3 — a request nobody has sized is answered rather than refused', function (): void {
    // The one optional field with a real answer, and the contrast is the point:
    // every other absence here is a refusal. An unsized request is an ordinary
    // state of a queue, and `we do not know` is what belongs on the screen —
    // `0 bytes` beside a request for a whole season is worse than saying
    // nothing.
    $data = ['members' => [aMember('Robin', [aRequest(1, 'A film', 'getting')])]];

    $wanted = iterator_to_array(Households::in(householdSaying($data)), preserve_keys: false);

    expect($wanted)->toHaveCount(1)
        ->and($wanted[0]->size()->either(
            measured: static fn(int $bytes): Code => Code::of(sprintf('measured-%d', $bytes)),
            guessed: static fn(int $bytes): Code => Code::of(sprintf('guessed-%d', $bytes)),
            unknown: static fn(): Code => Code::of('unknown'),
        )->shown())->toBe('unknown');
});

it('D7-R3 — an estimate it cannot read is answered the same way as one that never came', function (): void {
    // The second of the two ways a size goes missing, landing where the first
    // one does. An estimate that arrived and makes no sense is still not a
    // reason to refuse the request: the row is decidable without a figure, and
    // *we do not know* is true of it either way. Refusing here would lose a
    // request over the one field that has an answer for not having one.
    $data = ['members' => [aMember('Robin', [[
        'id' => 1,
        'title' => 'A film',
        'state' => 'getting',
        'estimate' => ['bytes' => 'a lot', 'measured' => true],
    ]])]];

    $wanted = iterator_to_array(Households::in(householdSaying($data)), preserve_keys: false);

    expect($wanted)->toHaveCount(1)
        ->and($wanted[0]->size()->either(
            measured: static fn(int $bytes): Code => Code::of(sprintf('measured-%d', $bytes)),
            guessed: static fn(int $bytes): Code => Code::of(sprintf('guessed-%d', $bytes)),
            unknown: static fn(): Code => Code::of('unknown'),
        )->shown())->toBe('unknown');
});

it('counts requests within a member, so the refusal names the right one', function (): void {
    // The second request rather than the first, which is what makes the count
    // load-bearing: a position that never advanced, or advanced the wrong way,
    // would send an operator to a request that is fine while the unreadable one
    // sits further down the list.
    $data = ['members' => [aMember('Robin', [
        aRequest(1, 'A film', 'getting'),
        'not a request',
    ])]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'Request 1');
});

it('reads requests by position even where the wire numbered them', function (): void {
    // A payload whose lists arrived keyed rather than as arrays still has to
    // come out in order, because everything downstream reads by position — the
    // refusal that names `Request 1` among them. The keys are the wire's
    // business and are not carried past this fold.
    $data = ['members' => [['name' => 'Robin', 'requests' => [
        7 => aRequest(1, 'First', 'getting'),
        3 => aRequest(2, 'Second', 'here'),
    ]]]];

    $wanted = iterator_to_array(Households::in(householdSaying($data)), preserve_keys: false);

    expect($wanted)->toHaveCount(2)
        ->and($wanted[0]->forWhat())->toBe('First')
        ->and($wanted[1]->forWhat())->toBe('Second');
});

it('D7-R4 — an estimate says whether anybody measured it, and is read either way', function (): void {
    // Both arms, because one of them alone cannot tell the fold from its
    // opposite: a reader that answered *measured* for everything and one that
    // answered *guessed* for everything each pass a test that only ever sends
    // one. The two have to stay distinguishable.
    $data = ['members' => [aMember('Robin', [
        ['id' => 1, 'title' => 'Measured', 'state' => 'getting',
            'estimate' => ['bytes' => 4_000_000_000, 'measured' => true]],
        ['id' => 2, 'title' => 'Guessed', 'state' => 'getting',
            'estimate' => ['bytes' => 900_000_000, 'measured' => false]],
    ])]];

    $wanted = iterator_to_array(Households::in(householdSaying($data)), preserve_keys: false);

    $howBig = static fn(int $at): string => $wanted[$at]->size()->either(
        measured: static fn(int $bytes): Code => Code::of(sprintf('measured-%d', $bytes)),
        guessed: static fn(int $bytes): Code => Code::of(sprintf('guessed-%d', $bytes)),
        unknown: static fn(): Code => Code::of('unknown'),
    )->shown();

    expect($howBig(0))->toBe('measured-4000000000')
        ->and($howBig(1))->toBe('guessed-900000000');
});

it('D7-R3 — an estimate half-readable is no more use than none of it', function (): void {
    // The two halves of the check are asked separately, because a reader that
    // dropped either one would still pass a test that only ever breaks both.
    // A figure with no word for whether anybody measured it is a number an
    // operator cannot weigh, which is the same position as having no figure.
    $data = ['members' => [aMember('Robin', [
        ['id' => 1, 'title' => 'Bytes but no word', 'state' => 'getting',
            'estimate' => ['bytes' => 4_000_000_000, 'measured' => 'yes']],
        ['id' => 2, 'title' => 'Word but no bytes', 'state' => 'getting',
            'estimate' => ['bytes' => 'a lot', 'measured' => true]],
    ])]];

    $wanted = iterator_to_array(Households::in(householdSaying($data)), preserve_keys: false);

    $howBig = static fn(int $at): string => $wanted[$at]->size()->either(
        measured: static fn(int $bytes): Code => Code::of(sprintf('measured-%d', $bytes)),
        guessed: static fn(int $bytes): Code => Code::of(sprintf('guessed-%d', $bytes)),
        unknown: static fn(): Code => Code::of('unknown'),
    )->shown();

    expect($howBig(0))->toBe('unknown')
        ->and($howBig(1))->toBe('unknown');
});

it('N3-R7 — reads the reason a request was refused, and when', function (): void {
    $wanted = iterator_to_array(Households::in(householdSaying(['members' => [
        aMember('Robin', [[
            'id' => 1,
            'title' => 'A film',
            'state' => 'declined',
            'refused' => ['at' => '2026-09-14T04:00:00Z', 'reason' => 'The disk is nearly full'],
        ]]),
    ]])), preserve_keys: false);

    $said = $wanted[0]->refusal(
        was: static fn(TurnedDown $why): Code => Code::of(sprintf('%s/%s', $why->reason(), $why->when(
            then: static fn(string $when): Code => Code::of($when),
            unstated: static fn(): Code => Code::of('-'),
        )->shown())),
        wasNot: static fn(): Code => Code::of('not refused'),
    );

    expect($said->shown())->toBe('The disk is nearly full/2026-09-14T04:00:00Z');
});

it('a refusal timed as whitespace is read as one the stack did not time', function (): void {
    // `at` present and blank is not the same shape as `at` absent, and it is the
    // one a stack produces by accident — a field it always writes, filled with
    // nothing on the row where the moment was not known. Read untrimmed it is a
    // string, so it would reach a screen as a moment made of spaces and render
    // as a refusal that happened at no time anybody can read.
    foreach (['', ' ', '   ', "\t", "\n"] as $blank) {
        $wanted = iterator_to_array(Households::in(householdSaying(['members' => [
            aMember('Robin', [[
                'id' => 1,
                'title' => 'A film',
                'state' => 'declined',
                'refused' => ['at' => $blank, 'reason' => 'Not this week'],
            ]]),
        ]])), preserve_keys: false);

        $said = $wanted[0]->refusal(
            was: static fn(TurnedDown $why): Code => Code::of($why->when(
                then: static fn(string $when): Code => Code::of(sprintf('timed as `%s`', $when)),
                unstated: static fn(): Code => Code::of('unstated'),
            )->shown()),
            wasNot: static fn(): Code => Code::of('not refused'),
        );

        // `json_encode` rather than `var_export`: the point is to render a
        // string of whitespace visibly in the failure message, and both do
        // that — but `var_export` is on the list of calls that must not
        // survive a commit, and a legible assertion is not a reason to keep
        // one. This file held no class until now, which is the only reason
        // the rule had never seen it.
        expect($said->shown())->toBe('unstated', sprintf('a refusal timed as %s', json_encode($blank)));
    }
});

it('a refusal the stack did not time is read without one', function (): void {
    $wanted = iterator_to_array(Households::in(householdSaying(['members' => [
        aMember('Robin', [[
            'id' => 1,
            'title' => 'A film',
            'state' => 'declined',
            'refused' => ['reason' => 'Not this week'],
        ]]),
    ]])), preserve_keys: false);

    $said = $wanted[0]->refusal(
        was: static fn(TurnedDown $why): Code => Code::of($why->when(
            then: static fn(string $when): Code => Code::of($when),
            unstated: static fn(): Code => Code::of('unstated'),
        )->shown()),
        wasNot: static fn(): Code => Code::of('not refused'),
    );

    expect($said->shown())->toBe('unstated');
});

it('D7-R7 — a declined request carrying no reason is refused, not shown short', function (): void {
    // `declined` with nothing after it is the screen that sends somebody to ask
    // their operator in person, which is the whole thing the requirement exists
    // to prevent — and substituting *no reason given* would be this app writing
    // a sentence on a stack's behalf.
    $each = [
        'no refused key at all' => ['id' => 1, 'title' => 'A film', 'state' => 'declined'],
        'a refusal that is not one' => ['id' => 1, 'title' => 'A film', 'state' => 'declined', 'refused' => 'no'],
        'a blank reason' => ['id' => 1, 'title' => 'A film', 'state' => 'declined', 'refused' => ['reason' => '  ']],
    ];

    foreach ($each as $which => $row) {
        expect(fn(): Requested => Households::in(householdSaying(['members' => [aMember('Robin', [$row])]])))
            ->toThrow(HouseholdIsUnreadable::class, 'no readable reason', $which);
    }
});

it('a request that is not declined is read whatever `refused` says', function (): void {
    // A row that is not declined and carries a refusal anyway is a stack that
    // changed its mind. The standing is the fact this app renders, and showing
    // somebody why a thing they are still waiting for was refused would be
    // worse than dropping it.
    $wanted = iterator_to_array(Households::in(householdSaying(['members' => [
        aMember('Robin', [[
            'id' => 1,
            'title' => 'A film',
            'state' => 'getting',
            'refused' => ['reason' => 'a reason from a decision that was reversed'],
        ]]),
    ]])), preserve_keys: false);

    $said = $wanted[0]->refusal(
        was: static fn(TurnedDown $why): Code => Code::of($why->reason()),
        wasNot: static fn(): Code => Code::of('not refused'),
    );

    expect($said->shown())->toBe('not refused');
});

it('stands in for a household with a payload the contract would accept', function (): void {
    // Held to the generated types rather than to the reader, because a fixture
    // is written by whoever wrote the reader: where the two agree about a field
    // that is not there, both are wrong in the same direction and every case
    // above is green against a machine nobody has run them against.
    //
    // A refused request as well as a plain one, because the refusal is a shape
    // of its own, and something has to read inside it.
    $payload = aHouseholdOf([aMember('Robin', [
        aRequest(1, 'A film nobody has seen', 'waiting-for-approval'),
        [...aRequest(2, 'A season', 'declined'), 'refused' => ['reason' => 'somebody said no']],
    ])]);

    expect(WhatTheContractAccepts::complaintsAbout('HouseholdEnvelope', ['kind' => 'household', 'data' => $payload]))
        ->toBe([], "The payload this suite stands in for a household with is not one a stack would send.\n");
});

/** One row's standing as a word, and what the unnamed arm reads as. */
function theStandingRead(Wanted $one): string
{
    return $one->standing()->either(
        said: static fn(Waiting $said): WhatThisRowStands => new WhatThisRowStands($said->value),
        unnamed: static fn(): WhatThisRowStands => new WhatThisRowStands('nobody named it'),
    )->said;
}

/** One standing carried out of `either()`, since it must hand back an object. */
final readonly class WhatThisRowStands
{
    public function __construct(public string $said) {}
}

it('N2-R11 — a status nobody named is a row, not the end of the reading', function (): void {
    // The contract leaves `state` out where the request service reported a
    // status lemonfiber has no word for, rather than guessing it into the
    // nearest one. Refusing it took the household down with it: one request
    // nobody had a word for made every member, every other request, and every
    // decision waiting on them unreadable — a surface asked to show what is
    // awaiting a decision, showing none of it.
    $data = aHouseholdOf([
        aMember('Robin', [
            ['id' => 1, 'title' => 'A film nobody has a word for'],
            aRequest(2, 'A season', 'waiting-for-approval'),
        ]),
    ]);

    $wanted = iterator_to_array(Households::in(householdSaying($data)), preserve_keys: false);
    $read = [];

    foreach ($wanted as $one) {
        $read[] = sprintf('%s/%s', $one->forWhat(), theStandingRead($one));
    }

    expect($read)->toBe([
        'A film nobody has a word for/nobody named it',
        'A season/waiting-for-approval',
    ]);
});

it('N2-R11 — a standing nobody named wants no decision, and is not a decline', function (): void {
    // Two things follow from *nobody named it* and both matter. Nothing offers
    // to approve it, because the app cannot say it is waiting. And it is not
    // read as a decline, which would demand the refusal sentence a declined row
    // owes — refusing the row for want of a sentence nobody wrote.
    $data = aHouseholdOf([aMember('Robin', [['id' => 1, 'title' => 'A film']])]);

    $wanted = Households::in(householdSaying($data));

    expect($wanted->waiting())->toBe(0)
        ->and($wanted->count())->toBe(1);
});

it('a standing that is not a word at all is refused, not read as absent', function (): void {
    // The third case, and it sits between the other two. Absent is an answer;
    // a word this app does not know is drift between the contract and the
    // enum; a number is neither — it is a payload the contract does not permit
    // in the first place, and reading it as *nobody named it* would turn a
    // malformed row into an ordinary one.
    $data = aHouseholdOf([aMember('Robin', [['id' => 1, 'title' => 'A film', 'state' => 7]])]);

    expect(static fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class);
});

it('a standing spelled out and unrecognised is still refused', function (): void {
    // The other half of the same decision, and the one that stays a fault. The
    // contract's union and `Waiting` are generated from one source, so a word
    // that reaches here and is not a case means the two have drifted — which is
    // about this app rather than about the household.
    $data = aHouseholdOf([aMember('Robin', [aRequest(1, 'A film', 'mislaid')])]);

    expect(static fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class);
});

it('stands in for a household the contract would accept, with a state left out', function (): void {
    // The claim the three cases above rest on: a request with no `state` is a
    // payload a stack may really send, not one invented to make a point.
    $data = aHouseholdOf([aMember('Robin', [['id' => 1, 'title' => 'A film']])]);

    expect(WhatTheContractAccepts::complaintsAbout('HouseholdEnvelope', ['kind' => 'household', 'data' => $data]))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});

/**
 * Everybody a household reading found, each as `name:joined` or `name:invited`.
 *
 * @param Envelope<mixed> $envelope
 */
function whoWasFoundIn(Envelope $envelope): string
{
    $found = [];

    foreach (Households::whoIsIn($envelope) as $member) {
        $found[] = sprintf('%s:%s', $member->name(), $member->hasJoined() ? 'joined' : 'invited');
    }

    return implode(',', $found);
}

it('reads everybody in the house, joined or still invited, in the stack\'s order', function (): void {
    $data = aHouseholdOf([aMember('Robin', []), [...aMember('Sam', []), 'claimed' => false]]);

    expect(whoWasFoundIn(householdSaying($data)))->toBe('Robin:joined,Sam:invited')
        ->and(whoWasFoundIn(householdSaying(aHouseholdOf([]))))->toBe('');
});

it('refuses to say who is in where the stack could not read the household, or the payload is not one', function (): void {
    expect(fn(): string => whoWasFoundIn(householdSaying([...aHouseholdOf([]), 'available' => false])))->toThrow(HouseholdIsUnreadable::class, 'could not read the household')
        ->and(fn(): string => whoWasFoundIn(new Envelope(1, 'household', 'a house')))->toThrow(HouseholdIsUnreadable::class, 'no `data`')
        ->and(fn(): string => whoWasFoundIn(householdSaying(['available' => true])))->toThrow(HouseholdIsUnreadable::class, 'no `members`');
});

it('refuses a member it cannot tell by name, or cannot tell joined from invited, and says which', function (): void {
    $table = [
        'not a member',
        ['claimed' => true],
        [...aMember('Robin', []), 'name' => 3],
        array_diff_key(aMember('Robin', []), ['claimed' => true]),
        [...aMember('Robin', []), 'claimed' => 'yes'],
    ];

    foreach ($table as $row) {
        expect(fn(): string => whoWasFoundIn(householdSaying(aHouseholdOf([aMember('Sam', []), $row]))))
            ->toThrow(HouseholdIsUnreadable::class, 'Member 1 in the household envelope is not a member');
    }
});
