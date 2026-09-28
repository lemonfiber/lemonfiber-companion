<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\AStoppage;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowItStopped;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatFollowedFromIt;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Operator\Internal\Presenters\AgoAsShown;
use Modules\Operator\Internal\Presenters\HowTheOneLineReads;
use Modules\Operator\Internal\ViewModels\AStoppageAsShown;
use Modules\Operator\Internal\ViewModels\WhatTheOneLineSays;

/** A moment, counted in minutes from one a test starts at. */
function minutesIn(int $minutes): Instant
{
    return Instant::atEpochSeconds(1_790_000_000 + $minutes * 60);
}

/** A summary counting one thing, with every part an item has. */
function aSummaryCountingOne(HowItStands $standing): TheHealthSummary
{
    return TheHealthSummary::of(
        $standing,
        1,
        'The disk is nearly full',
        WhatStoppedMoving::nothing(),
        AnAffectedItem::of(
            Check::of('disk.space'),
            Severity::Warning,
            'The disk is nearly full',
            'New downloads will start failing soon',
            Remedies::of(Remedy::of('Make room'), Remedy::of('Add a disk')),
            WhatFollowedFromIt::of('Imports are failing'),
        ),
    );
}

/** The line a screen draws, having heard these in turn at the first minute. */
function theLineAfter(Instant $now, WhatWasHeard ...$heard): WhatTheOneLineSays
{
    $held = WhatWasHeardSoFar::nothingYet();

    foreach ($heard as $one) {
        $held = $held->after($one, minutesIn(0));
    }

    return new HowTheOneLineReads()->of($held, $now);
}

it('says it is waiting, and nothing else, before anything has been heard', function (): void {
    $line = theLineAfter(minutesIn(0));

    expect($line->said)->toBe('health.summary.waiting')
        ->and($line->counted)->toBe('')
        ->and($line->howMany)->toBe(0)
        ->and($line->worst)->toBe('')
        ->and($line->ago->said)->toBe('')
        ->and($line->ago->count)->toBe(0)
        ->and($line->met)->toBe('')
        ->and($line->remedy)->toBe('')
        ->and($line->listening)->toBeFalse()
        ->and($line->affected)->toBe([]);
});

it('says it is waiting while a subscription is open and has said nothing yet', function (): void {
    $line = theLineAfter(minutesIn(0), WhatWasHeard::nothing());

    expect($line->said)->toBe('health.summary.waiting')
        ->and($line->listening)->toBeTrue();
});

it('reads as unknown, and says what stopped it, where a subscription could not be heard before it said anything', function (): void {
    $line = theLineAfter(minutesIn(0), WhatWasHeard::met(Obstacle::StackDidNotAnswer));

    expect($line->said)->toBe('health.standing.unknown')
        ->and($line->counted)->toBe('')
        ->and($line->howMany)->toBe(0)
        ->and($line->worst)->toBe('')
        ->and($line->ago->said)->toBe('')
        ->and($line->met)->toBe('connection.no_answer')
        ->and($line->remedy)->toBe('connection.no_answer_action')
        ->and($line->listening)->toBeFalse()
        ->and($line->affected)->toBe([]);
});

it('draws a current summary as the core said it, with every part of every item', function (): void {
    $line = theLineAfter(minutesIn(0), WhatWasHeard::said(aSummaryCountingOne(HowItStands::Degraded)));

    expect($line->said)->toBe('health.standing.degraded')
        ->and($line->counted)->toBe('health.summary.wanting')
        ->and($line->howMany)->toBe(1)
        ->and($line->worst)->toBe('The disk is nearly full')
        ->and($line->ago->said)->toBe('')
        ->and($line->ago->count)->toBe(0)
        ->and($line->met)->toBe('')
        ->and($line->remedy)->toBe('')
        ->and($line->listening)->toBeTrue()
        ->and($line->affected)->toHaveCount(1)
        ->and($line->affected[0]->check)->toBe('disk.space')
        ->and($line->affected[0]->severity)->toBe('health.severity.warning')
        ->and($line->affected[0]->summary)->toBe('The disk is nearly full')
        ->and($line->affected[0]->meaning)->toBe('New downloads will start failing soon')
        ->and($line->affected[0]->remedies)->toBe(['Make room', 'Add a disk'])
        ->and($line->affected[0]->downstream)->toBe(['Imports are failing']);
});

it('says each word in its own sentence, and counts the way the word asks', function (): void {
    $said = [];

    foreach (HowItStands::cases() as $standing) {
        $line = theLineAfter(minutesIn(0), WhatWasHeard::said(aSummaryCountingOne($standing)));
        $said[$standing->value] = [$line->said, $line->counted];
    }

    expect($said)->toBe([
        'healthy' => ['health.standing.healthy', 'health.summary.reported'],
        'stopped' => ['health.standing.stopped', 'health.summary.reported'],
        'unconfigured' => ['health.standing.unconfigured', 'health.summary.reported'],
        'advisory' => ['health.standing.advisory', 'health.summary.notes'],
        'degraded' => ['health.standing.degraded', 'health.summary.wanting'],
        'broken' => ['health.standing.broken', 'health.summary.wanting'],
        'critical' => ['health.standing.critical', 'health.summary.wanting'],
        'unknown' => ['health.standing.unknown', 'health.summary.reported'],
    ]);
});

it('counts nothing where the core counted nothing, and names nothing', function (): void {
    $line = theLineAfter(minutesIn(0), WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '', WhatStoppedMoving::nothing())));

    expect($line->said)->toBe('health.standing.healthy')
        ->and($line->counted)->toBe('')
        ->and($line->howMany)->toBe(0)
        ->and($line->worst)->toBe('')
        ->and($line->affected)->toBe([]);
});

it('reads a summary that is no longer current as unknown, with when it was heard and what it said', function (): void {
    $line = theLineAfter(
        minutesIn(5),
        WhatWasHeard::said(aSummaryCountingOne(HowItStands::Healthy)),
        WhatWasHeard::closed(),
    );

    expect($line->said)->toBe('health.standing.unknown')
        ->and($line->counted)->toBe('health.summary.reported')
        ->and($line->howMany)->toBe(1)
        ->and($line->worst)->toBe('The disk is nearly full')
        ->and($line->ago->said)->toBe('health.ago.minutes')
        ->and($line->ago->count)->toBe(5)
        ->and($line->met)->toBe('')
        ->and($line->remedy)->toBe('')
        ->and($line->listening)->toBeFalse()
        ->and($line->affected)->toHaveCount(1);
});

it('says what stopped a subscription beside what it last heard', function (): void {
    $line = theLineAfter(
        minutesIn(2),
        WhatWasHeard::said(aSummaryCountingOne(HowItStands::Critical)),
        WhatWasHeard::met(Obstacle::DeviceHasNoNetwork),
    );

    expect($line->said)->toBe('health.standing.unknown')
        ->and($line->met)->toBe('connection.no_network')
        ->and($line->remedy)->toBe('connection.no_network_action')
        ->and($line->ago->count)->toBe(2);
});

it('says a kept word with when it was heard, and nothing it did not keep', function (): void {
    $line = new HowTheOneLineReads()->kept(HowItStands::Critical, minutesIn(0), Instant::atEpochSeconds(1_790_000_030));

    expect($line)->toEqual(new WhatTheOneLineSays(
        said: 'health.standing.critical',
        counted: '',
        howMany: 0,
        worst: '',
        ago: AgoAsShown::from(HowLongAgo::Minutes, minutesIn(0), Instant::atEpochSeconds(1_790_000_030)),
        met: '',
        remedy: '',
        listening: false,
        affected: [],
        stopped: [],
        slow: [],
    ));
});

it('reads a kept word as unknown once a stream could no longer have vouched for it', function (): void {
    $line = new HowTheOneLineReads()->kept(HowItStands::Healthy, minutesIn(0), minutesIn(90));

    expect($line->said)->toBe('health.standing.unknown')
        ->and($line->ago->said)->toBe('health.ago.hours')
        ->and($line->ago->count)->toBe(1);
});

it('says a stack never heard cannot be told, with no age', function (): void {
    expect(new HowTheOneLineReads()->neverHeard())->toEqual(new WhatTheOneLineSays(
        said: 'health.standing.unknown',
        counted: '',
        howMany: 0,
        worst: '',
        ago: AgoAsShown::live(),
        met: '',
        remedy: '',
        listening: false,
        affected: [],
        stopped: [],
        slow: [],
    ));
});

/**
 * The names on stopped rows, in the order they are drawn.
 *
 * @param list<AStoppageAsShown> $rows
 *
 * @return list<string>
 */
function theNamesOn(array $rows): array
{
    $names = [];

    foreach ($rows as $row) {
        $names[] = $row->name;
    }

    return $names;
}

/** A summary with nothing counted, and these rows of what stopped moving. */
function aSummaryWhereThisStopped(AStoppage ...$rows): TheHealthSummary
{
    return TheHealthSummary::of(HowItStands::Degraded, 0, '', WhatStoppedMoving::of(...$rows));
}

it('draws what stopped moving apart from what is only slow, each in the order the stack sent', function (): void {
    $line = theLineAfter(minutesIn(0), WhatWasHeard::said(aSummaryWhereThisStopped(
        AStoppage::of(HowItStopped::RedownloadLoop, 'Arrival', 1, '', 7_200),
        AStoppage::of(HowItStopped::Slow, 'Dune', 1, '', 600),
        AStoppage::of(HowItStopped::Orphaned, 'Heat', 1, '', 90_000),
        AStoppage::of(HowItStopped::Slow, 'Alien', 1, '', 60),
    )));

    expect(theNamesOn($line->stopped))->toBe(['Arrival', 'Heat'])
        ->and(theNamesOn($line->slow))->toBe(['Dune', 'Alien']);
});

it('draws a row standing for several items once, naming the cause and the service\'s words, with nothing to follow', function (): void {
    $line = theLineAfter(minutesIn(0), WhatWasHeard::said(aSummaryWhereThisStopped(
        AStoppage::of(HowItStopped::RepeatedImportFailure, 'Permission denied', 20, 'Access to the path is denied.', 10_800),
    )));

    expect($line->stopped)->toEqual([new AStoppageAsShown(
        kindSaid: 'health.stopped.repeated-import-failure',
        name: 'Permission denied',
        items: 20,
        blocking: 'Access to the path is denied.',
        heldSaid: 'health.held_for.hours',
        heldCount: 3,
        follows: '',
    )]);
});

it('gives a row standing for one item that item to follow, and says how long in the unit it fills', function (): void {
    $line = theLineAfter(minutesIn(0), WhatWasHeard::said(aSummaryWhereThisStopped(
        AStoppage::of(HowItStopped::Slow, 'Dune', 1, '', 150),
    )));

    expect($line->slow)->toEqual([new AStoppageAsShown(
        kindSaid: 'health.stopped.slow',
        name: 'Dune',
        items: 1,
        blocking: '',
        heldSaid: 'health.held_for.minutes',
        heldCount: 2,
        follows: 'Dune',
    )]);
});

it('keeps what stopped moving from a summary that is no longer current, beside when it was heard', function (): void {
    $line = theLineAfter(
        minutesIn(5),
        WhatWasHeard::said(aSummaryWhereThisStopped(AStoppage::of(HowItStopped::Orphaned, 'Heat', 1, '', 60))),
        WhatWasHeard::closed(),
    );

    expect($line->said)->toBe('health.standing.unknown')
        ->and($line->stopped)->toHaveCount(1)
        ->and($line->ago->said)->toBe('health.ago.minutes');
});
