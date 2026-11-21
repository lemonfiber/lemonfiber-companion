<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\Presenters;

use function array_map;
use function expect;
use function implode;
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

use function str_starts_with;

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
 * Each cell, as its title or the titles it holds together, its width and its tap.
 *
 * @return list<array{string, int, string}>
 */
function cutOf(WhatAGuideRowSays $row): array
{
    return array_map(static fn(WhatACellSays $cell): array => [
        $cell->isSeveral() ? implode(' + ', array_map(static fn(WhatACellSays $one): string => $one->title, $cell->together)) : $cell->title,
        $cell->width,
        $cell->tap,
    ], $row->cells);
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
    $read = readAtTenPast(WhenItSays::unsaid(), from('A', 0, 17), from('B', 17, 29), from('C', 29, 41));

    expect(array_map(static fn(WhatACellSays $cell): int => $cell->width, $read->rows[0]->cells))->toBe([68, 48, 48]);
});

it('rounds each edge to the nearest point, so a programme off the minute is drawn neither short nor long', function (): void {
    $read = new HowTheGuideReads(Zone::utc(), Instant::atEpochSeconds(EIGHT))->these(TheGuide::of(AChannelsGuide::of(
        ChannelId::called('c1'),
        'NPO 1',
        '1',
        WhenItSays::unsaid(),
        AnAiring::of('A', Instant::atEpochSeconds(EIGHT), Instant::atEpochSeconds(EIGHT + 1630), ''),
        AnAiring::of('B', Instant::atEpochSeconds(EIGHT + 1630), Instant::atEpochSeconds(EIGHT + 3000), ''),
    )));

    expect(array_map(static fn(WhatACellSays $cell): int => $cell->width, $read->rows[0]->cells))->toBe([109, 91]);
});

it('draws whole programmes too short to press, one after another, as one cell across the time they run, which lists them', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('Now', 0, 30), from('A', 30, 35), from('B', 35, 40), from('C', 40, 45), from('Later', 45, 90));
    $together = $read->rows[0]->cells[1];

    expect(cutOf($read->rows[0]))->toBe([
        ['Now', 120, 'tuneTo(\'c1\')'],
        ['A + B + C', 60, 'listTheProgrammes(0, 1)'],
        ['Later', 180, 'aboutTheProgramme(0, 4)'],
    ])
        ->and([$together->times, $together->isOnNow, $together->at, $together->isAGap()])->toBe(['20:30–20:45', false, -1, false])
        ->and(array_map(static fn(WhatACellSays $one): array => [$one->times, $one->tap], $together->together))->toBe([
            ['20:30–20:35', 'aboutTheProgramme(0, 1)'],
            ['20:35–20:40', 'aboutTheProgramme(0, 2)'],
            ['20:40–20:45', 'aboutTheProgramme(0, 3)'],
        ]);
});

it('widens a short programme over the empty time after it first, keeping the rest of that time empty', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('Now', 0, 30), from('Short', 30, 35), from('Later', 60, 90));

    expect(cutOf($read->rows[0]))->toBe([
        ['Now', 120, 'tuneTo(\'c1\')'],
        ['Short', 48, 'aboutTheProgramme(0, 1)'],
        ['', 72, ''],
        ['Later', 120, 'aboutTheProgramme(0, 2)'],
    ]);
});

it('widens a short programme over the empty time before it where none follows it', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('Now', 0, 20), from('Short', 30, 35), from('Later', 35, 60));

    expect(cutOf($read->rows[0]))->toBe([
        ['Now', 80, 'tuneTo(\'c1\')'],
        ['', 12, ''],
        ['Short', 48, 'aboutTheProgramme(0, 1)'],
        ['Later', 100, 'aboutTheProgramme(0, 2)'],
    ]);
});

it('takes the programme after a short one into its cell where no empty time is beside it', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('Now', 0, 30), from('Short', 30, 35), from('Long', 35, 60));

    expect(cutOf($read->rows[0]))->toBe([
        ['Now', 120, 'tuneTo(\'c1\')'],
        ['Short + Long', 120, 'listTheProgrammes(0, 1)'],
    ])
        ->and($read->rows[0]->cells[1]->times)->toBe('20:30–21:00');
});

it('draws a short programme past its row\'s end where nothing is beside it, as wide as a finger needs', function (): void {
    $read = readAtTenPast(WhenItSays::at(Instant::atEpochSeconds(EIGHT + (35 * MINUTE))), from('Now', 0, 30), from('Short', 30, 35));

    expect(cutOf($read->rows[0]))->toBe([['Now', 120, 'tuneTo(\'c1\')'], ['Short', 48, 'aboutTheProgramme(0, 1)']]);
});

it('draws a programme the grid\'s start or the programme before it cut too short to press as an edge, which takes no press', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('Ending', -20, 5), from('Middle', 5, 60), from('Cut', 58, 62), from('Later', 62, 90));
    $edges = array_map(static fn(WhatACellSays $cell): array => [$cell->title, $cell->width, $cell->isAnEdge, $cell->tap, $cell->isAGap()], $read->rows[0]->cells);

    expect($edges)->toBe([
        ['Ending', 20, true, '', false],
        ['Middle', 220, false, 'tuneTo(\'c1\')', false],
        ['Cut', 8, true, '', false],
        ['Later', 112, false, 'aboutTheProgramme(0, 3)', false],
    ]);
});

it('keeps every long programme where its time is, whatever the short ones around it take of the empty time', function (): void {
    $read = readAtTenPast(
        WhenItSays::unsaid(),
        from('Long one', 0, 26),
        from('a', 26, 29),
        from('b', 29, 30),
        from('Long two', 40, 70),
        from('c', 70, 71),
        from('Long three', 85, 120),
    );
    $placed = [];
    $left = 0;

    foreach ($read->rows[0]->cells as $cell) {
        if (str_starts_with($cell->title, 'Long')) {
            $placed[$cell->title] = [$left, $cell->width];
        }

        $left += $cell->width;
    }

    expect($placed)->toBe(['Long one' => [0, 104], 'Long two' => [160, 120], 'Long three' => [340, 140]])
        ->and(cutOf($read->rows[0]))->toBe([
            ['Long one', 104, 'tuneTo(\'c1\')'],
            ['a + b', 48, 'listTheProgrammes(0, 1)'],
            ['', 8, ''],
            ['Long two', 120, 'aboutTheProgramme(0, 3)'],
            ['c', 48, 'aboutTheProgramme(0, 4)'],
            ['', 12, ''],
            ['Long three', 140, 'aboutTheProgramme(0, 5)'],
        ]);
});

it('takes all the empty time beside short programmes where that is not enough, then the programme after them', function (): void {
    $after = readAtTenPast(WhenItSays::unsaid(), from('Now', 0, 30), from('Short', 30, 35), from('Long', 37, 60));
    $before = readAtTenPast(WhenItSays::unsaid(), from('Now', 0, 20), from('Short', 25, 30), from('Long', 30, 60));

    expect(cutOf($after->rows[0]))->toBe([['Now', 120, 'tuneTo(\'c1\')'], ['Short + Long', 120, 'listTheProgrammes(0, 1)']])
        ->and(cutOf($before->rows[0]))->toBe([['Now', 80, 'tuneTo(\'c1\')'], ['Short + Long', 160, 'listTheProgrammes(0, 1)']]);
});

it('marks several drawn together as on now where one of them is', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('A', 9, 11), from('B', 11, 21));

    expect(cutOf($read->rows[0]))->toBe([['', 36, ''], ['A + B', 48, 'listTheProgrammes(0, 1)']])
        ->and($read->rows[0]->cells[1]->isOnNow)->toBeTrue();
});

it('draws no empty space for time between two programmes too short to reach a point', function (): void {
    $read = new HowTheGuideReads(Zone::utc(), Instant::atEpochSeconds(EIGHT))->these(TheGuide::of(AChannelsGuide::of(
        ChannelId::called('c1'),
        'NPO 1',
        '1',
        WhenItSays::unsaid(),
        AnAiring::of('A', Instant::atEpochSeconds(EIGHT), Instant::atEpochSeconds(EIGHT + 1800), ''),
        AnAiring::of('B', Instant::atEpochSeconds(EIGHT + 1807), Instant::atEpochSeconds(EIGHT + 3600), ''),
    )));

    expect(cutOf($read->rows[0]))->toBe([['A', 120, 'tuneTo(\'c1\')'], ['B', 120, 'aboutTheProgramme(0, 1)']]);
});

it('takes the empty time after an edge it took in, where a short programme and the edge are still too narrow together', function (): void {
    $read = readAtTenPast(WhenItSays::unsaid(), from('Now', 0, 30), from('Short', 30, 35), from('Over', 33, 37), from('Later', 45, 60));

    expect(cutOf($read->rows[0]))->toBe([
        ['Now', 120, 'tuneTo(\'c1\')'],
        ['Short + Over', 48, 'listTheProgrammes(0, 1)'],
        ['', 12, ''],
        ['Later', 60, 'aboutTheProgramme(0, 3)'],
    ])
        ->and($read->rows[0]->cells[1]->times)->toBe('20:30–20:37');
});
