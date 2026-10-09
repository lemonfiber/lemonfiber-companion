<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Internal;

use function expect;
use function it;

use Modules\Dx\Internal\WhereTheyLeftOffAsItReads;

it('gives each item a reason in place of a location', function (): void {
    $read = WhereTheyLeftOffAsItReads::from(['data' => ['member' => 'ada', 'part_way' => [['id' => 'a1', 'stream_from' => 'Stream from'], 'not an item']]]);

    expect($read['data'])->toBe(['member' => 'ada', 'part_way' => [
        ['id' => 'a1', 'stream_from' => null, 'door' => null, 'unlocated' => 'unlocated'],
        ['stream_from' => null, 'door' => null, 'unlocated' => 'unlocated'],
    ]]);
});

it('answers nothing part-way through where the declaration left the list out', function (): void {
    expect(WhereTheyLeftOffAsItReads::from(['data' => 'not a table'])['data'])->toBe(['part_way' => []]);
});
