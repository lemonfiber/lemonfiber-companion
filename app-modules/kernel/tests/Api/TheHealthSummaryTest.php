<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAffectedItem;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\SummaryCountsBelowNothing;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatFollowedFromIt;

/** One affected item, named by its check. */
function anItemCalled(string $check): AnAffectedItem
{
    return AnAffectedItem::of(
        Check::of($check),
        Severity::Error,
        'Downloads have stopped',
        'Nothing new arrives',
        Remedies::of(Remedy::of('Restart the client')),
        WhatFollowedFromIt::of('Imports are waiting', 'Requests are waiting'),
    );
}

it('holds the word, the count and the worst thing as the core sent them', function (): void {
    $summary = TheHealthSummary::of(HowItStands::Broken, 2, 'Downloads have stopped', anItemCalled('queue.stalled'), anItemCalled('disk.space'));

    expect($summary->standing())->toBe(HowItStands::Broken)
        ->and($summary->wantingAttention())->toBe(2)
        ->and($summary->worst())->toBe('Downloads have stopped');
});

it('walks the affected items in the order the core put them in', function (): void {
    $checks = [];

    foreach (TheHealthSummary::of(HowItStands::Broken, 2, '', anItemCalled('queue.stalled'), anItemCalled('disk.space')) as $item) {
        $checks[] = $item->check()->shown();
    }

    expect($checks)->toBe(['queue.stalled', 'disk.space']);
});

it('walks the affected items by position, whatever they were handed over keyed by', function (): void {
    $keyed = ['first' => anItemCalled('queue.stalled'), 'second' => anItemCalled('disk.space')];

    expect(array_keys(iterator_to_array(TheHealthSummary::of(HowItStands::Broken, 2, '', ...$keyed), preserve_keys: true)))->toBe([0, 1]);
});

it('holds nothing to walk and nothing named where nothing is wrong', function (): void {
    $summary = TheHealthSummary::of(HowItStands::Healthy, 0, '');

    expect(iterator_to_array($summary, preserve_keys: false))->toBe([])
        ->and($summary->wantingAttention())->toBe(0)
        ->and($summary->worst())->toBe('');
});

it('refuses a count below nothing, and takes nothing as a count', function (): void {
    expect(static fn(): TheHealthSummary => TheHealthSummary::of(HowItStands::Healthy, -1, ''))
        ->toThrow(SummaryCountsBelowNothing::class, 'said -1 things')
        ->and(TheHealthSummary::of(HowItStands::Healthy, 0, '')->wantingAttention())->toBe(0);
});

it('holds every part of an affected item as the core sent it', function (): void {
    $item = anItemCalled('queue.stalled');
    $remedies = [];

    foreach ($item->remedies() as $remedy) {
        $remedies[] = $remedy->action();
    }

    expect($item->check()->shown())->toBe('queue.stalled')
        ->and($item->severity())->toBe(Severity::Error)
        ->and($item->summary())->toBe('Downloads have stopped')
        ->and($item->meaning())->toBe('Nothing new arrives')
        ->and($remedies)->toBe(['Restart the client'])
        ->and(iterator_to_array($item->downstream(), preserve_keys: false))->toBe(['Imports are waiting', 'Requests are waiting'])
        ->and($item->downstream()->count())->toBe(2);
});

it('holds nothing downstream where nothing followed from it', function (): void {
    expect(WhatFollowedFromIt::of()->count())->toBe(0)
        ->and(iterator_to_array(WhatFollowedFromIt::of(), preserve_keys: false))->toBe([]);
});
