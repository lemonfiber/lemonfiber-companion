<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Waiting;
use Modules\Sdk\Api\HouseholdIsUnreadable;
use Modules\Sdk\Api\Households;

use function sprintf;

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
 * One member, with the requests they made.
 *
 * @param  list<mixed> $requests
 * @return array<string, mixed>
 */
function aMember(string $name, array $requests): array
{
    return ['name' => $name, 'requests' => $requests];
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
    $data = ['members' => [
        aMember('Robin', [aRequest(1, 'A film', 'waiting-for-approval')]),
        aMember('Sam', [aRequest(2, 'A season', 'getting')]),
    ]];

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

it('refuses a member with no requests key rather than reading it as a quiet week', function (): void {
    $data = ['members' => [['name' => 'Robin']]];

    expect(fn(): Requested => Households::in(householdSaying($data)))
        ->toThrow(HouseholdIsUnreadable::class, 'requests');
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
