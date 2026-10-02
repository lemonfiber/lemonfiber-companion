<?php

declare(strict_types=1);

namespace Modules\News\Tests\Internal;

use function array_keys;
use function array_map;
use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;
use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;
use Modules\News\Internal\TheNewsAsKept;
use Modules\News\Internal\WhatIsKeptOfNews;

use function sprintf;

/** What was kept, as one line to compare. */
function whatWasKeptOfNews(WhatIsKeptOfNews $kept): string
{
    $seen = array_map(static fn(AnItem $item): string => sprintf('%s:%d', $item->named(), $item->order()), $kept->everySeen());
    $off = array_map(static fn(KindOfNews $kind): string => $kind->value, $kept->switchedOff());

    return sprintf('off[%s] seen[%s]', implode(',', $off), implode(',', array_map(static fn(string $kind, string $item): string => sprintf('%s=%s', $kind, $item), array_keys($seen), $seen)));
}

it('reads back the kinds switched off and the newest of each kind seen that it wrote', function (): void {
    $kept = WhatIsKeptOfNews::nothing()
        ->notMarking(KindOfNews::Request)
        ->seeing(AnItem::anUpdate('0.17.0'))
        ->seeing(AnItem::aProblem('service.gluetun', Instant::atEpochSeconds(1_790_000_000)));

    expect(whatWasKeptOfNews(TheNewsAsKept::read(Shape::One, TheNewsAsKept::written($kept))))
        ->toBe('off[request] seen[update=0.17.0:0,problem=service.gluetun:1790000000]');
});

it('writes only what names and orders each item, and an empty record as one', function (): void {
    expect(TheNewsAsKept::written(WhatIsKeptOfNews::nothing()->seeing(AnItem::aRequest(42)))->inTheClear())
        ->toBe('{"off":[],"seen":{"request":{"known_as":"42","order":42}}}')
        ->and(TheNewsAsKept::written(WhatIsKeptOfNews::nothing())->inTheClear())->toBe('{"off":[],"seen":[]}');
});

it('reads what does not read as nothing switched off and nothing seen, part by part', function (string $written, string $read): void {
    expect(whatWasKeptOfNews(TheNewsAsKept::read(Shape::One, Unsealed::of($written))))->toBe($read);
})->with([
    'not an object' => ['"off"', 'off[] seen[]'],
    'nothing in it' => ['{}', 'off[] seen[]'],
    'a list of kinds that is not one' => ['{"off":"update"}', 'off[] seen[]'],
    'a kind a later build added' => ['{"off":["update","alert"]}', 'off[update] seen[]'],
    'a kind that is not text' => ['{"off":[1,"problem"]}', 'off[problem] seen[]'],
    'a kind named twice' => ['{"off":["problem","problem"]}', 'off[problem] seen[]'],
    'a marker with no name' => ['{"seen":{"request":{"order":1}}}', 'off[] seen[]'],
    'a marker with no order' => ['{"seen":{"request":{"known_as":"1"}}}', 'off[] seen[]'],
    'a marker named by a number' => ['{"seen":{"request":{"known_as":1,"order":1}}}', 'off[] seen[]'],
    'a marker ordered by text' => ['{"seen":{"request":{"known_as":"1","order":"1"}}}', 'off[] seen[]'],
    'a request numbered nothing' => ['{"seen":{"request":{"known_as":"0","order":0}}}', 'off[] seen[]'],
    'an update named by nothing, beside one that reads' => ['{"seen":{"update":{"known_as":"","order":0},"request":{"known_as":"3","order":3}}}', 'off[] seen[request=3:3]'],
]);
