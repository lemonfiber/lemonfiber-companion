<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AStoppage;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\StoppageSaysNothing;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Sdk\Api\SummaryIsUnreadable;
use Modules\Sdk\Internal\StoppedRows;

use function sprintf;

/**
 * One stopped row with every part the reader insists on, and whatever a case changes.
 *
 * @param array<string, mixed> $changed
 *
 * @return array<string, mixed>
 */
function aStoppedRow(array $changed = []): array
{
    return [
        'stall' => 'repeated-import-failure',
        'name' => 'Permission denied on /media/films',
        'items' => 20,
        'blocking' => 'Access to the path is denied.',
        'held_for' => 10_800,
        ...$changed,
    ];
}

/**
 * The rows read out of a payload holding these.
 *
 * @param array<mixed> $rows
 *
 * @return list<AStoppage>
 */
function theStoppedRowsIn(array $rows): array
{
    return iterator_to_array(StoppedRows::in(['stuck' => $rows]), preserve_keys: false);
}

/**
 * What reading these rows refused, by the message it refused with.
 *
 * @param array<mixed> $data
 */
function whyTheStoppedRowsWereRefused(array $data): string
{
    try {
        StoppedRows::in($data);
    } catch (SummaryIsUnreadable $why) {
        return $why->getMessage();
    }

    return 'nothing was refused';
}

it('reads every field of a row that stands for several items', function (): void {
    [$row] = theStoppedRowsIn([aStoppedRow()]);

    expect($row->how())->toBe(HowItStopped::RepeatedImportFailure)
        ->and($row->name())->toBe('Permission denied on /media/films')
        ->and($row->items())->toBe(20)
        ->and($row->blocking())->toBe('Access to the path is denied.')
        ->and($row->heldFor()->inSeconds())->toBe(10_800);
});

it('reads every kind of stopped the contract has', function (): void {
    foreach (HowItStopped::cases() as $how) {
        [$row] = theStoppedRowsIn([aStoppedRow(['stall' => $how->value])]);

        expect($row->how())->toBe($how);
    }
});

it('keeps the order the stack sent, rather than ranking the rows again', function (): void {
    $rows = theStoppedRowsIn([
        aStoppedRow(['stall' => 'slow', 'name' => 'Dune']),
        aStoppedRow(['stall' => 'redownload-loop', 'name' => 'Arrival']),
    ]);

    expect($rows[0]->name())->toBe('Dune')
        ->and($rows[1]->name())->toBe('Arrival');
});

it('reads a service that said nothing about what blocked it as nothing said, whether left out or sent as nothing', function (): void {
    $leftOut = aStoppedRow();
    unset($leftOut['blocking']);

    [$absent] = theStoppedRowsIn([$leftOut]);
    [$null] = theStoppedRowsIn([aStoppedRow(['blocking' => null])]);

    expect($absent->blocking())->toBe('')
        ->and($null->blocking())->toBe('');
});

it('reads an empty list as nothing stopped', function (): void {
    expect(StoppedRows::in(['stuck' => []]))->toEqual(WhatStoppedMoving::nothing());
});

it('refuses a payload with no stopped list, or one that is not a list', function (): void {
    expect(whyTheStoppedRowsWereRefused([]))->toContain('`stuck`')
        ->and(whyTheStoppedRowsWereRefused(['stuck' => 'nothing']))->toContain('`stuck`');
});

it('refuses a row it cannot read, naming the row rather than always the first', function (): void {
    $each = [
        'a row that is not a row' => ['a sentence', 'stuck'],
        'a row with a kind that is not text' => [aStoppedRow(['stall' => 4]), 'stall'],
        'a row with no name' => [aStoppedRow(['name' => null]), 'name'],
        'a row whose count is not a number' => [aStoppedRow(['items' => 'twenty']), 'items'],
        'a row whose time is not a number' => [aStoppedRow(['held_for' => '3h']), 'held_for'],
        'a row whose blocking is not text' => [aStoppedRow(['blocking' => ['denied']]), 'blocking'],
    ];

    foreach ($each as [$row, $field]) {
        expect(whyTheStoppedRowsWereRefused(['stuck' => [aStoppedRow(), $row]]))
            ->toContain('Stopped row 1 ')
            ->toContain(sprintf('`%s`', $field));
    }
});

it('refuses a row missing a field it insists on', function (): void {
    foreach (['stall', 'name', 'items', 'held_for'] as $field) {
        $row = aStoppedRow();
        unset($row[$field]);

        expect(whyTheStoppedRowsWereRefused(['stuck' => [$row]]))
            ->toContain('Stopped row 0 ')
            ->toContain(sprintf('`%s`', $field));
    }
});

it('refuses a kind of stopped it does not read, naming the kinds it does', function (): void {
    expect(whyTheStoppedRowsWereRefused(['stuck' => [aStoppedRow(), aStoppedRow(['stall' => 'sulking'])]]))
        ->toContain('Stopped row 1 ')
        ->toContain('`sulking`')
        ->toContain('`redownload-loop`, `repeated-import-failure`, `completed-not-imported`, `orphaned`, `stalled-download`, `waiting-indefinitely`, `slow`');
});

it('hands a blank name and a count of nothing to the value that refuses them', function (): void {
    expect(static fn(): array => theStoppedRowsIn([aStoppedRow(['name' => '  '])]))
        ->toThrow(StoppageSaysNothing::class)
        ->and(static fn(): array => theStoppedRowsIn([aStoppedRow(['items' => 0])]))
        ->toThrow(StoppageSaysNothing::class);
});
