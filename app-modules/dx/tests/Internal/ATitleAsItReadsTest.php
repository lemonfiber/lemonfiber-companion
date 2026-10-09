<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Internal;

use function expect;
use function it;

use Modules\Dx\Internal\ATitleAsItReads;

it('gives a title the declaration left out a reason in place of a location, no release day and no seasons', function (): void {
    $read = ATitleAsItReads::from(['data' => ['id' => 'a1']]);

    expect($read['data'])->toBe(['id' => 'a1', 'title' => [
        'stream_from' => null,
        'door' => null,
        'unlocated' => 'unlocated',
        'released' => null,
        'seasons' => [],
    ]]);
});

it('gives each episode of each season a reason in place of a location', function (): void {
    $read = ATitleAsItReads::from(['data' => ['title' => ['seasons' => [['name' => 'Season 1', 'episodes' => [['id' => 'e1']]], 'not a season']]]]);

    expect($read['data'])->toBe(['title' => [
        'seasons' => [
            ['name' => 'Season 1', 'episodes' => [['id' => 'e1', 'stream_from' => null, 'door' => null, 'unlocated' => 'unlocated']]],
            ['episodes' => []],
        ],
        'stream_from' => null,
        'door' => null,
        'unlocated' => 'unlocated',
        'released' => null,
    ]]);
});
