<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\Presenters;

use function array_map;
use function expect;
use function it;

use Modules\Household\Internal\Presenters\HowTheGuideReads;
use Modules\Household\Internal\ViewModels\WhatACellSays;
use Modules\Household\Internal\ViewModels\WhatAGuideRowSays;
use Modules\Household\Internal\ViewModels\WhatAMarkSays;
use Modules\Household\Internal\ViewModels\WhatTheGuideTurnedOutToBe;
use Modules\Kernel\Api\AChannelsGuide;
use Modules\Kernel\Api\AnAiring;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheGuide;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\Zone;

/** Eight in the evening, UTC: the half hour these grids start at. */
const EIGHT = 1_893_441_600;

/** A minute, in seconds. */
const MINUTE = 60;

/** One channel's guide, read at ten past eight UTC. */
function readAtTenPast(WhenItSays $until, AnAiring ...$airings): WhatTheGuideTurnedOutToBe
{
    return new HowTheGuideReads(Zone::utc(), Instant::atEpochSeconds(EIGHT + (10 * MINUTE)))
        ->these(TheGuide::of(AChannelsGuide::of(ChannelId::called('c1'), 'NPO 1', '1', $until, ...$airings)));
}

/** A programme from so many minutes after eight to so many. */
function from(string $title, int $starts, int $ends): AnAiring
{
    return AnAiring::of($title, Instant::atEpochSeconds(EIGHT + ($starts * MINUTE)), Instant::atEpochSeconds(EIGHT + ($ends * MINUTE)), '');
}

/**
 * Each cell, as its title, width, whether it is a gap, whether it is on now, and where it sat as sent.
 *
 * @return list<array{string, int, bool, bool, int}>
 */
function cellsOf(WhatAGuideRowSays $row): array
{
    return array_map(static fn(WhatACellSays $cell): array => [$cell->title, $cell->width, $cell->isAGap(), $cell->isOnNow, $cell->at], $row->cells);
}

it('draws a programme four points to the minute, in start order whatever order the core sent', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('Later', 60, 90), from('Now', 0, 30));

    expect(cellsOf($read->rows[0]))->toBe([['Now', 120, false, true, 1], ['', 120, true, false, -1], ['Later', 120, false, false, 0]])
        ->and([$read->rows[0]->cells[1]->times, $read->rows[0]->cells[1]->certificate, $read->rows[0]->cells[1]->tap])->toBe(['', '', '']);
});

it('leaves out a programme that ended before the grid starts, and draws one that began before it from the start', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('Over', -60, -30), from('Begun', -15, 15));

    expect(cellsOf($read->rows[0]))->toBe([['Begun', 60, false, true, 1]])
        ->and($read->rows[0]->cells[0]->times)->toBe('19:45–20:15');
});

it('draws a programme that overlaps the one before it from where that one ends', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('First', 0, 40), from('Second', 30, 60));

    expect(cellsOf($read->rows[0]))->toBe([['First', 160, false, true, 0], ['Second', 80, false, false, 1]]);
});

it('draws no cell for a programme with no length, though the hours still reach it', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('Nothing', 45, 45));

    expect($read->rows[0]->cells)->toBe([])
        ->and(array_map(static fn(WhatAMarkSays $mark): string => $mark->time, $read->marks))->toBe(['20:00', '20:30']);
});

it('stops a row where its guide ends, and the half hours where the furthest row does', function (): void {
    $read = readAtTenPast(WhenItSays::at(Instant::atEpochSeconds(EIGHT + (75 * MINUTE))), from('Now', 0, 30));

    expect(cellsOf($read->rows[0]))->toBe([['Now', 120, false, true, 0]])
        ->and(array_map(static fn(WhatAMarkSays $mark): array => [$mark->time, $mark->width], $read->marks))->toBe([['20:00', 120], ['20:30', 120], ['21:00', 120]]);
});

it('reads a programme on now from its start until just before its end', function (): void {
    $starting = readAtTenPast(WhenItSays::unsaid(), from('Starting', 10, 30));
    $ending = readAtTenPast(WhenItSays::unsaid(), from('Ending', 0, 10));

    expect($starting->rows[0]->cells[1]->isOnNow)->toBeTrue()
        ->and($starting->rows[0]->cells[1]->tap)->toBe('tuneTo(\'c1\')')
        ->and($ending->rows[0]->cells[0]->isOnNow)->toBeFalse()
        ->and($ending->rows[0]->cells[0]->tap)->toBe('aboutTheProgramme(0, 0)');
});

it('names a channel by its number and name together, or its name alone where it has no number', function (): void {
    $read = new HowTheGuideReads(Zone::utc(), Instant::atEpochSeconds(EIGHT))->these(TheGuide::of(
        AChannelsGuide::of(ChannelId::called('c1'), 'NPO 1', '1', WhenItSays::unsaid()),
        AChannelsGuide::of(ChannelId::called('c2'), 'Radio', '', WhenItSays::unsaid()),
    ));

    expect(array_map(static fn(WhatAGuideRowSays $row): array => [$row->id, $row->name, $row->number, $row->shown, $row->tap], $read->rows))->toBe([
        ['c1', 'NPO 1', '1', '1 · NPO 1', 'tuneTo(\'c1\')'],
        ['c2', 'Radio', '', 'Radio', 'tuneTo(\'c2\')'],
    ]);
});

it('widens nothing to keep up: widths add up to where each moment is drawn from the start', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('A', 0, 7), from('B', 7, 13), from('C', 13, 20));

    expect(array_map(static fn(WhatACellSays $cell): int => $cell->width, $read->rows[0]->cells))->toBe([28, 24, 28]);
});

it('rounds each edge to the nearest point, so a programme off the minute is drawn neither short nor long', function (): void {
    $read = new HowTheGuideReads(Zone::utc(), Instant::atEpochSeconds(EIGHT))->these(TheGuide::of(AChannelsGuide::of(
        ChannelId::called('c1'),
        'NPO 1',
        '1',
        WhenItSays::unsaid(),
        AnAiring::of('A', Instant::atEpochSeconds(EIGHT), Instant::atEpochSeconds(EIGHT + 430), ''),
        AnAiring::of('B', Instant::atEpochSeconds(EIGHT + 430), Instant::atEpochSeconds(EIGHT + 800), ''),
    )));

    expect(array_map(static fn(WhatACellSays $cell): int => $cell->width, $read->rows[0]->cells))->toBe([29, 24]);
});
