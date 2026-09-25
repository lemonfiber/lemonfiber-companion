<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatFollowedFromIt;
use Modules\Kernel\Api\WhatWasHeard;
use Modules\Operator\Internal\Presenters\HowTheOneLineReads;
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
    $line = theLineAfter(minutesIn(0), WhatWasHeard::said(TheHealthSummary::of(HowItStands::Healthy, 0, '')));

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
