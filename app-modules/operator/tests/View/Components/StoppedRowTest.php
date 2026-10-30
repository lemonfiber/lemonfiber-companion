<?php

declare(strict_types=1);

use Illuminate\Contracts\Translation\Translator;
use Modules\Operator\Internal\ViewModels\AStoppageAsShown;
use Modules\Operator\View\Components\PortRow;
use Modules\Operator\View\Components\StoppedRow;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

uses(TestCase::class);

/** A stopped row for these items, with what the service said was in the way. */
function aStoppedRow(int $items, string $blocking, string $trace): StoppedRow
{
    $row = new AStoppageAsShown('health.stopped_kind.stalled', 'Dune', $items, $blocking, 'health.ago.minutes', 5, $trace);

    return new StoppedRow(app(Translator::class), $row, $trace, 'fine');
}

it('says how many items share a cause only where the row stands for more than one', function (int $items, bool $shared): void {
    $said = aStoppedRow($items, '', '')->said;

    expect(str_contains($said, trans_choice('health.stopped_stands_for', $items)))->toBe($shared);
})->with([
    'one' => [1, false],
    'two' => [2, true],
]);

it('says what the service said was in the way only where it said something', function (): void {
    expect(aStoppedRow(1, 'disk full', '')->said)->toContain(__('health.stopped_blocking', ['words' => 'disk full']))
        ->and(aStoppedRow(1, '', '')->said)->not->toContain(__('health.stopped_blocking', ['words' => '']));
});

it('reads a row that leads somewhere as where it leads and what is said of it, and a row that leads nowhere as nothing more', function (): void {
    $leads = aStoppedRow(1, '', 'dune');

    expect($leads->named)->toBe(implode(PortRow::BETWEEN, [WhatMarkupDraws::words('health.trace.road_in', ['item' => 'Dune']), $leads->said]))
        ->and(aStoppedRow(1, '', '')->named)->toBe('');
});
