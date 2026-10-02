<?php

declare(strict_types=1);

namespace Modules\News\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Instant;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\NotAnItem;
use Modules\News\Api\TheItems;
use Modules\News\Api\WhatIsNew;

/** Three releases, newest first. */
function threeReleases(): TheItems
{
    return TheItems::of(KindOfNews::Update, AnItem::anUpdate('0.17.0'), AnItem::anUpdate('0.16.0'), AnItem::anUpdate('0.15.0'));
}

/**
 * What is new, named.
 *
 * @return list<string>
 */
function namedIn(WhatIsNew $new): array
{
    return array_map(static fn(AnItem $item): string => $item->named(), iterator_to_array($new, preserve_keys: false));
}

it('holds items of one kind, newest first', function (): void {
    $items = threeReleases();

    expect($items->kind())->toBe(KindOfNews::Update)
        ->and($items)->toHaveCount(3)
        ->and(namedIn(WhatIsNew::these(...$items)))->toBe(['0.17.0', '0.16.0', '0.15.0']);
});

it('refuses an item of another kind', function (): void {
    expect(static fn(): TheItems => TheItems::of(KindOfNews::Update, AnItem::anUpdate('0.17.0'), AnItem::aRequest(3)))
        ->toThrow(NotAnItem::class, 'A list of update items was handed an item of the kind request.');
});

it('finds the releases above the one seen in the record newer than it', function (): void {
    expect(threeReleases()->newerThan(AnItem::anUpdate('0.15.0')))->toHaveCount(2)
        ->and(WhatIsNew::nothing())->toHaveCount(0)
        ->and(namedIn(threeReleases()->newerThan(AnItem::anUpdate('0.15.0'))))->toBe(['0.17.0', '0.16.0'])
        ->and(namedIn(threeReleases()->newerThan(AnItem::anUpdate('0.17.0'))))->toBe([]);
});

it('leaves only the newest release new where the one seen is no longer in the record', function (): void {
    expect(namedIn(threeReleases()->newerThan(AnItem::anUpdate('0.9.0'))))->toBe(['0.17.0'])
        ->and(namedIn(TheItems::of(KindOfNews::Update)->newerThan(AnItem::anUpdate('0.9.0'))))->toBe([]);
});

it('finds the requests numbered above the one seen, and the problems begun after it', function (): void {
    $requests = TheItems::of(KindOfNews::Request, AnItem::aRequest(9), AnItem::aRequest(7), AnItem::aRequest(4));
    $problems = TheItems::of(
        KindOfNews::Problem,
        AnItem::aProblem('service.gluetun', Instant::atEpochSeconds(300)),
        AnItem::aProblem('storage.full', Instant::atEpochSeconds(200)),
    );

    expect(namedIn($requests->newerThan(AnItem::aRequest(7))))->toBe(['9'])
        ->and(namedIn($requests->newerThan(AnItem::aRequest(9))))->toBe([])
        ->and(namedIn($problems->newerThan(AnItem::aProblem('storage.full', Instant::atEpochSeconds(200)))))->toBe(['service.gluetun']);
});

it('says which of two items is newer by the order of their kind', function (): void {
    $requests = TheItems::of(KindOfNews::Request, AnItem::aRequest(9), AnItem::aRequest(7));

    expect(threeReleases()->isNewer(AnItem::anUpdate('0.17.0'), AnItem::anUpdate('0.16.0')))->toBeTrue()
        ->and(threeReleases()->isNewer(AnItem::anUpdate('0.16.0'), AnItem::anUpdate('0.17.0')))->toBeFalse()
        ->and(threeReleases()->isNewer(AnItem::anUpdate('0.16.0'), AnItem::anUpdate('0.16.0')))->toBeFalse()
        ->and(threeReleases()->isNewer(AnItem::anUpdate('0.15.0'), AnItem::anUpdate('0.9.0')))->toBeTrue()
        ->and(threeReleases()->isNewer(AnItem::anUpdate('0.9.0'), AnItem::anUpdate('0.15.0')))->toBeFalse()
        ->and($requests->isNewer(AnItem::aRequest(9), AnItem::aRequest(7)))->toBeTrue()
        ->and($requests->isNewer(AnItem::aRequest(7), AnItem::aRequest(7)))->toBeFalse();
});
