<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_map;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use LogicException;
use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhereItStopsShort;
use Modules\Sdk\Api\HistoryIsUnreadable;
use Modules\Sdk\Api\Records;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `history` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see hostingSaying()}'s reason: what
 * is under test is what happens when the wire says something the contract does
 * not allow, which a client honouring the contract could never produce.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function historySaying(array $data): Envelope
{
    return new Envelope(1, 'history', $data);
}

/**
 * One change with every part the reader insists on.
 *
 * @return array<string, mixed>
 */
function aRecordedChange(string $did = 'Pointed Sonarr at the new library', string $at = '1790142840'): array
{
    return [
        'did' => $did,
        'operation' => 'reconfigure',
        'target' => 'sonarr',
        'at' => $at,
        'reversal' => 'whole',
        'alongside' => 1,
    ];
}

/**
 * A record of these rows, reaching back as far as the usual horizon.
 *
 * @param list<mixed> $changes
 *
 * @return Envelope<mixed>
 */
function aRecordOf(array $changes): Envelope
{
    return historySaying(['horizon' => 'The last 90 days', 'changes' => $changes]);
}

/**
 * The first change a record holds.
 *
 * Every case reading one row reads it this way, so a record that came back
 * empty fails on the row being absent rather than passing on a default.
 */
function theFirstChangeIn(TheRecord $record): Change
{
    foreach ($record as $change) {
        return $change;
    }

    throw new LogicException('The record held no change.');
}

/** One line carried out of the fold. */
final readonly class WhatTheRecordedChangeSaid
{
    public function __construct(public string $said) {}
}

/** Where putting a change back stops short, as one line, or the word for nowhere. */
function whatARecordedChangeSaysOfItsLimits(Change $change): string
{
    return $change->stopsShort(
        there: static fn(WhereItStopsShort $where): WhatTheRecordedChangeSaid => new WhatTheRecordedChangeSaid(
            sprintf('%s / %s', $where->why(), $where->instead(
                said: static fn(string $what): WhatTheRecordedChangeSaid => new WhatTheRecordedChangeSaid($what),
                nothing: static fn(): WhatTheRecordedChangeSaid => new WhatTheRecordedChangeSaid('-'),
            )->said),
        ),
        nowhere: static fn(): WhatTheRecordedChangeSaid => new WhatTheRecordedChangeSaid('nowhere'),
    )->said;
}

it('N11-R1 — reads the record with how far back it goes, keeping the stack\'s order', function (): void {
    $record = Records::in(aRecordOf([aRecordedChange('Newer'), aRecordedChange('Older', '1790110000')]));

    $done = [];

    foreach ($record as $change) {
        $done[] = $change->did();
    }

    expect($record->horizon())->toBe('The last 90 days')
        ->and($done)->toBe(['Newer', 'Older']);
});

it('reads every part of one change', function (): void {
    $change = theFirstChangeIn(Records::in(aRecordOf([[...aRecordedChange(), 'reversal' => 'partial', 'alongside' => 3]])));

    expect($change->did())->toBe('Pointed Sonarr at the new library')
        ->and($change->operation())->toBe('reconfigure')
        ->and($change->target())->toBe('sonarr')
        ->and($change->when()->isTheSameMomentAs(WhenItWasMade::at(Instant::atEpochSeconds(1_790_142_840))))->toBeTrue()
        ->and($change->reversal())->toBe(HowFarItGoesBack::Partial)
        ->and($change->alongside())->toBe(3);
});

it('N11-R3 — a change that came alone says one, which is the least it can say', function (): void {
    // The boundary itself. One is this change and nothing else; below it is
    // refused further down, and this is the value either side of that line.
    expect(theFirstChangeIn(Records::in(aRecordOf([aRecordedChange()])))->alongside())->toBe(1);
});

it('N11-R9 — a record of nothing is an answer rather than a gap', function (): void {
    expect(Records::in(aRecordOf([]))->count())->toBe(0);
});

it('N11-R2 — carries why putting it back stops short, and what to do instead', function (): void {
    $change = theFirstChangeIn(Records::in(aRecordOf([[
        ...aRecordedChange(),
        'reversal' => 'partial',
        'because' => 'The old library was deleted',
        'instead' => 'Restore it from the last backup first',
    ]])));

    expect(whatARecordedChangeSaysOfItsLimits($change))
        ->toBe('The old library was deleted / Restore it from the last backup first');
});

it('reads an absent reason and a null one as the same silence', function (): void {
    // The contract describes both as a string or null, and a stack may leave
    // either out. Neither is a blank to refuse: it is the arm that says
    // nothing stops it short.
    $absent = theFirstChangeIn(Records::in(aRecordOf([aRecordedChange()])));
    $null = theFirstChangeIn(Records::in(aRecordOf([[...aRecordedChange(), 'because' => null, 'instead' => null]])));

    expect(whatARecordedChangeSaysOfItsLimits($absent))->toBe('nowhere')
        ->and(whatARecordedChangeSaysOfItsLimits($null))->toBe('nowhere');
});

it('carries a reason with nothing to suggest, and a null suggestion is that too', function (): void {
    $absent = theFirstChangeIn(Records::in(aRecordOf([[...aRecordedChange(), 'because' => 'Gone']])));
    $null = theFirstChangeIn(Records::in(aRecordOf([[...aRecordedChange(), 'because' => 'Gone', 'instead' => null]])));

    expect(whatARecordedChangeSaysOfItsLimits($absent))->toBe('Gone / -')
        ->and(whatARecordedChangeSaysOfItsLimits($null))->toBe('Gone / -');
});

it('refuses a suggestion that arrives without a reason', function (): void {
    // The stack builds both from one refusal to go further, and that refusal
    // always has a reason. A suggestion alone tells an operator to go and do
    // something without saying what it would fix.
    expect(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'instead' => 'Restore it']])))
        ->toThrow(HistoryIsUnreadable::class, 'what to do instead and not why')
        ->and(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'because' => null, 'instead' => 'Restore it']])))
        ->toThrow(HistoryIsUnreadable::class, 'what to do instead and not why');
});

it('refuses a reason or a suggestion that is there and says nothing', function (): void {
    // Blank is not silence. Silence is absent or null; blank is a sentence
    // that arrived empty, and drawn it is a heading with nothing under it.
    expect(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'because' => '  ']])))
        ->toThrow(HistoryIsUnreadable::class, '`because`')
        ->and(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'because' => 'Gone', 'instead' => 7]])))
        ->toThrow(HistoryIsUnreadable::class, '`instead`');
});

it('refuses an envelope whose payload is not a payload at all', function (): void {
    expect(fn(): TheRecord => Records::in(new Envelope(1, 'history', 'not a payload')))
        ->toThrow(HistoryIsUnreadable::class, '`data`');
});

it('N11-R1 — refuses a record that will not say how far back it goes', function (): void {
    // Absent, blank and not a word are one fault: the oldest row would read as
    // the machine's first day rather than the end of what is kept.
    expect(fn(): TheRecord => Records::in(historySaying(['changes' => []])))
        ->toThrow(HistoryIsUnreadable::class, '`horizon`')
        ->and(fn(): TheRecord => Records::in(historySaying(['horizon' => '   ', 'changes' => []])))
        ->toThrow(HistoryIsUnreadable::class, '`horizon`')
        ->and(fn(): TheRecord => Records::in(historySaying(['horizon' => 90, 'changes' => []])))
        ->toThrow(HistoryIsUnreadable::class, '`horizon`');
});

it('refuses a record with no changes key, and one whose changes are not changes', function (): void {
    expect(fn(): TheRecord => Records::in(historySaying(['horizon' => 'The last 90 days'])))
        ->toThrow(HistoryIsUnreadable::class, '`changes`')
        ->and(fn(): TheRecord => Records::in(historySaying(['horizon' => 'The last 90 days', 'changes' => 'many'])))
        ->toThrow(HistoryIsUnreadable::class, '`changes`');
});

it('names the position of the row it refused rather than always the first', function (): void {
    // A refusal naming row 0 about a fault in row 1 sends somebody to read the
    // wrong change, which in a record of forty is most of the work.
    expect(fn(): TheRecord => Records::in(aRecordOf([aRecordedChange(), 'not a change at all'])))
        ->toThrow(HistoryIsUnreadable::class, 'Change 1 ');
});

it('refuses a row missing any of the words it must carry, naming which', function (): void {
    foreach (['did', 'operation', 'target', 'at', 'reversal'] as $field) {
        $row = aRecordedChange();
        unset($row[$field]);

        expect(fn(): TheRecord => Records::in(aRecordOf([$row])))
            ->toThrow(HistoryIsUnreadable::class, sprintf('`%s`', $field));
    }
});

it('refuses a row whose word is there and blank, which is the same fault', function (): void {
    expect(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'did' => '   ']])))
        ->toThrow(HistoryIsUnreadable::class, '`did`');
});

it('N11-R10 — reads a zero stamp as an unreadable clock, never as 1970', function (): void {
    // Zero is what the stack writes where its clock would not answer. Read as
    // a moment it would put a change in 1970, confidently, on a screen.
    $unreadable = theFirstChangeIn(Records::in(aRecordOf([aRecordedChange(at: '0')])))->when();
    $known = theFirstChangeIn(Records::in(aRecordOf([aRecordedChange(at: '1')])))->when();

    expect($unreadable->isTheSameMomentAs(WhenItWasMade::unreadable()))->toBeFalse()
        ->and($unreadable->either(
            at: static fn(Instant $at): WhatTheRecordedChangeSaid => new WhatTheRecordedChangeSaid(sprintf('%d', $at->epochSeconds())),
            unreadable: static fn(): WhatTheRecordedChangeSaid => new WhatTheRecordedChangeSaid('unreadable'),
        )->said)->toBe('unreadable')
        // The second after it is a moment: only zero means *nobody could tell*.
        ->and($known->isTheSameMomentAs(WhenItWasMade::at(Instant::atEpochSeconds(1))))->toBeTrue();
});

it('N11-R10 — refuses a when that is not seconds since the epoch written as digits', function (): void {
    // Each is something the core does not write. An ISO date would need a
    // guess at a format; a sign or a space is a conversion being forgiving
    // about a stamp that came from somewhere else; digits too long to fit are
    // not a moment at all.
    foreach (['2026-09-23T03:14:00Z', '+1790142840', ' 1790142840', '1790142840 ', '-5', '00', '99999999999999999999'] as $said) {
        expect(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'at' => $said]])))
            ->toThrow(HistoryIsUnreadable::class, 'seconds since the epoch');
    }
});

it('refuses a when that arrives as a number rather than as the string the contract describes', function (): void {
    expect(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'at' => 1790142840]])))
        ->toThrow(HistoryIsUnreadable::class, '`at`');
});

it('N11-R2 — a reversal this app does not read names what it does read', function (): void {
    expect(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'reversal' => 'mostly']])))->toThrow(
        HistoryIsUnreadable::class,
        implode(', ', array_map(
            static fn(HowFarItGoesBack $reversal): string => sprintf('`%s`', $reversal->value),
            HowFarItGoesBack::cases(),
        )),
    );
});

it('N11-R3 — refuses a count of what came with a change that cannot be true', function (): void {
    // Absent, below one, and not a whole number are all refused: the count
    // includes the change it is on, so none of them is a small number.
    $without = aRecordedChange();
    unset($without['alongside']);

    expect(fn(): TheRecord => Records::in(aRecordOf([$without])))
        ->toThrow(HistoryIsUnreadable::class, 'at least one')
        ->and(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'alongside' => 0]])))
        ->toThrow(HistoryIsUnreadable::class, 'at least one')
        ->and(fn(): TheRecord => Records::in(aRecordOf([[...aRecordedChange(), 'alongside' => '3']])))
        ->toThrow(HistoryIsUnreadable::class, 'at least one');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    // The valid body, judged by the contract rather than by whoever wrote the
    // reader — `G12`. With both optional fields set, because the refusals
    // above are about departing from this shape and this says the shape is
    // real.
    expect(WhatTheContractAccepts::complaintsAbout('HistoryEnvelope', [
        'api_version' => 1,
        'kind' => 'history',
        'data' => [
            'horizon' => 'The last 90 days',
            'changes' => [[
                ...aRecordedChange(),
                'reversal' => 'partial',
                'because' => 'The old library was deleted',
                'instead' => 'Restore it from the last backup first',
            ]],
        ],
    ]))->toBe([], "The payload this suite reads is not one a stack would send.\n");
});
