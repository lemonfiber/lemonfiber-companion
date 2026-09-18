<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Waiting;
use Modules\Sdk\Api\HouseholdIsUnreadable;
use Modules\Sdk\Api\Households;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

use function var_export;

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
        ->and($wanted[1]->standing())->toBe(Waiting::Getting);
});

it('refuses a payload that is not an object at all', function (): void {
    // The generated envelope asserts its shape without checking it, which is
    // why the payload is read as `mixed` in the first place: an assertion is a
    // claim about the contract, not a fact about the socket.
    expect(fn(): Requested => Households::in(new Envelope(1, 'household', 'sorry')))
        ->toThrow(HouseholdIsUnreadable::class, 'data');
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

it('refuses a request with no state, for the same reason as one with no title', function (): void {
    $data = ['members' => [aMember('Robin', [['id' => 1, 'title' => 'A film']])]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'Request 0');
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

        expect($said->shown())->toBe('unstated', sprintf('a refusal timed as %s', var_export($blank, return: true)));
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
