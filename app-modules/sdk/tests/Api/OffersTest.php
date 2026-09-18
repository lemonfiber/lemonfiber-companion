<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\LeftBehind;
use Modules\Kernel\Api\OfferHasNoName;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\WhatBecameOfIt;
use Modules\Sdk\Api\OfferIsUnreadable;
use Modules\Sdk\Api\Offers;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `repair` envelope, likewise.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function repairSaying(array $data): Envelope
{
    return new Envelope(1, 'repair', $data);
}

/**
 * One offered repair with every part named, so a case can drop exactly one.
 *
 * Composed rather than mutated after the fact, which is `ReportsTest`'s
 * argument: a fixture built once and then indexed into is an `array<mixed>` the
 * analyser refuses to reach through, and reaching through it anyway is the
 * habit this parser exists to stop.
 *
 * @param array<string, mixed> $changed
 *
 * @return array<string, mixed>
 */
function anOfferedRepair(array $changed = []): array
{
    return [
        'check' => 'storage.one-filesystem',
        'does' => 'Move the library onto the larger disk',
        'effects' => ['Downloads pause while it moves'],
        'reversible' => true,
        ...$changed,
    ];
}

/**
 * One answer carried out of an arm, which hands back objects.
 *
 * `StoppagesTest`'s `WhatOneStuckRowSaid` one endpoint over, and the same
 * argument: an arm returns an object so that a caller cannot fold two arms into
 * a nullable string. The envelope type is not the thing to carry it in —
 * an envelope is what one program sends another, and a body under a kind the
 * contract has not got is a conversation neither end could have had.
 */
final readonly class WhatOneOfferSaid
{
    public function __construct(public string $said) {}
}

/**
 * A whole listing around one repair.
 *
 * @param array<string, mixed> $repair
 *
 * @return array<string, mixed>
 */
function aListingOf(array $repair): array
{
    return [
        'acted' => false,
        'agreement' => 'agreement-a-test-can-name',
        'beyond' => [],
        'mended' => [],
        'offered' => [$repair],
    ];
}

it('N2-R4 — reads all three clauses off an offered repair', function (): void {
    $offer = Offers::offerIn(repairSaying(aListingOf(anOfferedRepair())));
    $said = '';

    foreach ($offer->repairs() as $repair) {
        $said = $repair->stated(
            static fn(string $does, Effects $effects, Undoing $undoing): WhatOneOfferSaid
                => new WhatOneOfferSaid(sprintf('%s/%d/%s', $does, $effects->count(), $undoing->value)),
        )->said;
    }

    expect($offer->named())->toBe('agreement-a-test-can-name')
        ->and($said)->toBe('Move the library onto the larger disk/1/possible');
});

it('N2-R4 — reads reversible as the word rather than carrying the boolean', function (): void {
    // The one field on this screen where reading it backwards means telling
    // somebody a thing can be undone when it cannot, which is why the wire's
    // boolean does not survive the boundary.
    $permanent = Offers::offerIn(repairSaying(aListingOf(anOfferedRepair(['reversible' => false]))));
    $said = [];

    foreach ($permanent->repairs() as $repair) {
        $said[] = $repair->stated(
            static fn(string $does, Effects $effects, Undoing $undoing): WhatOneOfferSaid
                => new WhatOneOfferSaid($undoing->value),
        )->said;
    }

    expect($said)->toBe([Undoing::Permanent->value]);
});

it('refuses a listing with no agreement to quote back', function (): void {
    // A yes quotes the listing it was given. A listing with no name
    // is one a confirmation could not quote, and the failure would otherwise
    // appear at the moment of agreeing rather than at the moment of reading.
    $without = ['acted' => false, 'beyond' => [], 'mended' => [], 'offered' => []];

    expect(fn(): object => Offers::offerIn(repairSaying($without)))
        ->toThrow(OfferIsUnreadable::class, 'agreement');
});

it('refuses a listing whose agreement is blank', function (): void {
    expect(fn(): object => Offers::offerIn(repairSaying([
        'acted' => false,
        'agreement' => '  ',
        'beyond' => [],
        'mended' => [],
        'offered' => [],
    ])))->toThrow(OfferHasNoName::class);
});

it('refuses a listing with no offered repairs field at all', function (): void {
    // Told apart from an empty one below: a field that is absent is an envelope
    // this app cannot read, and an empty list is a stack with nothing to fix.
    expect(fn(): object => Offers::offerIn(repairSaying([
        'acted' => false,
        'agreement' => 'named',
        'beyond' => [],
        'mended' => [],
    ])))->toThrow(OfferIsUnreadable::class, 'offered');
});

it('reads a stack with nothing to put right as an empty listing', function (): void {
    // The healthy case, and an answer rather than an absence.
    $offer = Offers::offerIn(repairSaying([...aListingOf([]), 'offered' => []]));

    expect($offer->repairs()->isEmpty())->toBeTrue()
        ->and($offer->named())->toBe('agreement-a-test-can-name');
});

it('refuses a repair missing any one of the three clauses', function (): void {
    // Each dropped on its own, because a parser that read two of the three
    // would satisfy a test that dropped all of them. A repair is one thing with
    // three halves — a listing short of any one is one the operator must not be
    // shown.
    $each = [
        'does' => ['check' => 'storage.one-filesystem', 'effects' => [], 'reversible' => true],
        'check' => ['does' => 'Move it', 'effects' => [], 'reversible' => true],
        'effects' => ['check' => 'storage.one-filesystem', 'does' => 'Move it', 'reversible' => true],
        'reversible' => ['check' => 'storage.one-filesystem', 'does' => 'Move it', 'effects' => []],
    ];

    foreach ($each as $dropped => $repair) {
        expect(fn(): object => Offers::offerIn(repairSaying(aListingOf($repair))))
            ->toThrow(OfferIsUnreadable::class, 'Repair 0', sprintf('dropping `%s`', $dropped));
    }
});

it('refuses a repair whose fields are there and the wrong type', function (): void {
    // Present-and-wrong is the shape a presence check alone lets through, and
    // the one a stack of a newer version is most likely to produce.
    $each = [
        'does' => anOfferedRepair(['does' => 41]),
        'reversible' => anOfferedRepair(['reversible' => 'yes']),
        'effects' => anOfferedRepair(['effects' => 'a sentence rather than a list']),
        'an effect' => anOfferedRepair(['effects' => [41]]),
    ];

    foreach ($each as $wrong => $repair) {
        expect(fn(): object => Offers::offerIn(repairSaying(aListingOf($repair))))
            ->toThrow(OfferIsUnreadable::class, 'Repair 0', sprintf('`%s` of the wrong type', $wrong));
    }
});

it('refuses a repair whose fields are blank rather than absent', function (): void {
    // Blank as well as absent, and reported by this reader rather than raised
    // one layer down. `Check::of()` and `Repair::offered()` each refuse a blank
    // by throwing their own kind, and neither is caught in `Menders` — so a
    // stack sending either would reach the operator as a crash where every
    // other short payload reaches them as an obstacle.
    //
    // Both fields, because a reader that trimmed one would look finished: they
    // are the two `said()` serves and the one that goes untrimmed is whichever
    // was not the reason somebody opened this file.
    $each = [
        'check' => anOfferedRepair(['check' => '   ']),
        'does' => anOfferedRepair(['does' => "\t\n"]),
    ];

    foreach ($each as $blank => $repair) {
        expect(fn(): object => Offers::offerIn(repairSaying(aListingOf($repair))))
            ->toThrow(OfferIsUnreadable::class, 'Repair 0', sprintf('blank `%s`', $blank));
    }
});

it('refuses a row in the listing that is not a repair at all', function (): void {
    expect(fn(): object => Offers::offerIn(repairSaying([
        'acted' => false,
        'agreement' => 'named',
        'beyond' => [],
        'mended' => [],
        'offered' => ['a sentence where a repair belongs'],
    ])))->toThrow(OfferIsUnreadable::class, 'Repair 0');
});

it('refuses a listing whose payload is not a shape at all', function (): void {
    expect(fn(): object => Offers::offerIn(new Envelope(1, 'repair', 41)))
        ->toThrow(OfferIsUnreadable::class, 'data');
});

it('refuses a listing whose offered field is there and is not a list', function (): void {
    // Present-and-wrong rather than absent, which a presence check alone lets
    // through and a newer stack is the likeliest thing to produce.
    expect(fn(): object => Offers::offerIn(repairSaying([
        'acted' => false,
        'agreement' => 'named',
        'beyond' => [],
        'mended' => [],
        'offered' => 'a sentence where a list belongs',
    ])))->toThrow(OfferIsUnreadable::class, 'offered');
});

it('refuses a listing whose agreement is there and is not text', function (): void {
    expect(fn(): object => Offers::offerIn(repairSaying([
        'acted' => false,
        'agreement' => 41,
        'beyond' => [],
        'mended' => [],
        'offered' => [],
    ])))->toThrow(OfferIsUnreadable::class, 'agreement');
});

it('names the position of the row it refused rather than always the first', function (): void {
    // Every other case in this file refuses the only row there is, and `Repair
    // 0` is also what a counter that never moved would say — so none of them
    // can tell the counter going up from the counter going anywhere else.
    //
    // `effects()` already argues for this case in prose: "a listing of six
    // whose fourth is missing its consequences", where the position is the one
    // thing that makes it findable. A good row followed by a bad one is the
    // shortest listing that asks it.
    //
    // Both refusal sites, because they count from the same variable and only
    // one of them is on the path a wrong type takes.
    $each = [
        'a row that is not a repair at all' => 'a sentence where a repair belongs',
        'a repair with its consequences missing' => [
            'check' => 'storage.one-filesystem',
            'does' => 'Move it',
            'reversible' => true,
        ],
    ];

    foreach ($each as $second => $row) {
        expect(fn(): object => Offers::offerIn(repairSaying([
            'acted' => false,
            'agreement' => 'agreement-a-test-can-name',
            'beyond' => [],
            'mended' => [],
            'offered' => [anOfferedRepair(), $row],
        ])))->toThrow(OfferIsUnreadable::class, 'Repair 1', sprintf('second row: %s', $second));
    }
});

/**
 * One record of what became of a repair.
 *
 * @param array<string, mixed> $outcome
 *
 * @return array<string, mixed>
 */
function anOutcomeOf(array $outcome): array
{
    return ['repair' => anOfferedRepair(), 'outcome' => $outcome];
}

/**
 * A whole record around one thing in the mended list.
 *
 * Takes `mixed` rather than an array because half of what this file drives
 * through it is not an array at all — that is the point of those cases.
 *
 * @return array<string, mixed>
 */
function aRunOf(mixed $one): array
{
    return [
        'acted' => true,
        'agreement' => 'agreement-a-test-can-name',
        'beyond' => [],
        'offered' => [],
        'mended' => [$one],
    ];
}

it('N2-R5 — reads what became of a repair, and what a stopped one left', function (): void {
    $run = Offers::mendedIn(repairSaying(aRunOf(anOutcomeOf([
        'outcome' => 'stopped',
        'leaving' => 'Half of it on the old disk',
    ]))));

    $said = '';

    foreach ($run as $one) {
        $said = $one->said(
            static fn(Repair $repair, WhatBecameOfIt $became, LeftBehind $left): WhatOneOfferSaid
                => new WhatOneOfferSaid($left->either(
                    something: static fn(string $what): WhatOneOfferSaid
                        => new WhatOneOfferSaid(sprintf('%s/%s', $became->value, $what)),
                    nothing: static fn(): WhatOneOfferSaid => new WhatOneOfferSaid($became->value),
                )->said),
        )->said;
    }

    expect($said)->toBe('stopped/Half of it on the old disk')
        ->and($run->count())->toBe(1)
        ->and($run->changed())->toBe(0);
});

it('reads a stopped repair that left nothing as having left nothing', function (): void {
    // The one optional field here with a real answer: a repair that stopped and
    // left nothing can be agreed to again without a thought, and that is worth
    // saying rather than refusing to say.
    $run = Offers::mendedIn(repairSaying(aRunOf(anOutcomeOf(['outcome' => 'stopped']))));

    foreach ($run as $one) {
        $said = $one->said(
            static fn(Repair $repair, WhatBecameOfIt $became, LeftBehind $left): WhatOneOfferSaid
                => new WhatOneOfferSaid($left->either(
                    something: static fn(string $what): WhatOneOfferSaid
                        => new WhatOneOfferSaid(sprintf('left %s', $what)),
                    nothing: static fn(): WhatOneOfferSaid => new WhatOneOfferSaid('left nothing'),
                )->said),
        )->said;

        expect($said)->toBe('left nothing');
    }
});

it('refuses a run with no mended list at all', function (): void {
    expect(fn(): object => Offers::mendedIn(repairSaying([
        'acted' => true,
        'agreement' => 'named',
        'beyond' => [],
        'offered' => [],
    ])))->toThrow(OfferIsUnreadable::class, 'mended');
});

it('refuses a record that is not a record of a repair', function (): void {
    // Told apart from an unreadable *offer*: one is a listing nobody has agreed
    // to yet, and this is a report of what a machine already did. A reader who
    // cannot tell which cannot tell whether anything happened.
    $each = [
        'a sentence where a record belongs',
        ['outcome' => ['outcome' => 'fixed']],
        ['repair' => anOfferedRepair()],
        ['repair' => 'not a repair', 'outcome' => ['outcome' => 'fixed']],
        ['repair' => anOfferedRepair(), 'outcome' => 'not an outcome'],
        ['repair' => anOfferedRepair(), 'outcome' => ['outcome' => 41]],
    ];

    foreach ($each as $at => $one) {
        expect(fn(): object => Offers::mendedIn(repairSaying(aRunOf($one))))
            ->toThrow(OfferIsUnreadable::class, 'Outcome 0', sprintf('case %d', $at));
    }
});

it('refuses an outcome word this app does not read', function (): void {
    // Guessing is how a repair that overwrote nothing gets shown as one that
    // worked.
    expect(fn(): object => Offers::mendedIn(repairSaying(aRunOf(anOutcomeOf(['outcome' => 'mostly'])))))
        ->toThrow(OfferIsUnreadable::class, 'mostly');
});

it('names which record it could not read, rather than the envelope', function (): void {
    // The second one, so the position has to be carried to pass — the same
    // argument the offered listing makes one method over.
    $run = [
        'acted' => true,
        'agreement' => 'named',
        'beyond' => [],
        'offered' => [],
        'mended' => [anOutcomeOf(['outcome' => 'fixed']), 'not a record'],
    ];

    expect(fn(): object => Offers::mendedIn(repairSaying($run)))
        ->toThrow(OfferIsUnreadable::class, 'Outcome 1');
});

it('refuses a run whose payload is not a shape at all', function (): void {
    expect(fn(): object => Offers::mendedIn(new Envelope(1, 'repair', 'a sentence')))
        ->toThrow(OfferIsUnreadable::class, 'data');
});

it('reads a description of what was left that says nothing as nothing', function (): void {
    // Trimmed rather than taken at its word: a `leaving` of spaces renders as a
    // line of nothing under a heading saying something was left, which is worse
    // than the heading alone — and `LeftBehind::of()` would refuse it, turning
    // a readable answer into an unreadable envelope.
    $run = Offers::mendedIn(repairSaying(aRunOf(anOutcomeOf([
        'outcome' => 'stopped',
        'leaving' => '   ',
    ]))));

    foreach ($run as $one) {
        $said = $one->said(
            static fn(Repair $repair, WhatBecameOfIt $became, LeftBehind $left): WhatOneOfferSaid
                => new WhatOneOfferSaid($left->either(
                    something: static fn(string $what): WhatOneOfferSaid
                        => new WhatOneOfferSaid(sprintf('left %s', $what)),
                    nothing: static fn(): WhatOneOfferSaid => new WhatOneOfferSaid('left nothing'),
                )->said),
        )->said;

        expect($said)->toBe('left nothing');
    }
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    // Both readings, because they are two payloads under one kind: what a stack
    // would do, and what it did. Held to the generated types rather than to the
    // reader, since a fixture is written by whoever wrote the reader and the
    // two then agree about a field that is not there.
    $payloads = [
        'the listing' => aListingOf(anOfferedRepair()),
        'the record' => aRunOf(anOutcomeOf(['outcome' => 'stopped', 'leaving' => 'Half of it on the old disk'])),
    ];

    foreach ($payloads as $which => $payload) {
        expect(WhatTheContractAccepts::complaintsAbout('RepairEnvelope', ['kind' => 'repair', 'data' => $payload]))
            ->toBe([], sprintf(
                "The payload this suite stands in for a stack with is not one a stack would send: %s.\n",
                $which,
            ));
    }
});
