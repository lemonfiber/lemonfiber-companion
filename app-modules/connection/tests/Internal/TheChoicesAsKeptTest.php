<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Internal;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Connection\Internal\TheChoicesAsKept;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhatMayReachThePhone;

it('reads back the kinds switched off and the floor, in the shape they were written in', function (Severity $floor): void {
    $chosen = WhatMayReachThePhone::everything()->withOff(KindOfNotification::Member)->withOff(KindOfNotification::Backup)->withFloor($floor);
    $read = TheChoicesAsKept::read(Shape::One, TheChoicesAsKept::written($chosen));

    expect(iterator_to_array($read->switchedOff(), preserve_keys: false))->toBe([KindOfNotification::Backup, KindOfNotification::Member])
        ->and($read->floor())->toBe($floor);
})->with(Severity::cases());

it('writes the kinds and the floor by their wire words', function (): void {
    expect(TheChoicesAsKept::written(WhatMayReachThePhone::everything()->withOff(KindOfNotification::Update)->withFloor(Severity::Error))->inTheClear())
        ->toBe('{"kinds_off":["update"],"problems_from":"error"}');
});

it('reads what it cannot make out toward telling more', function (string $written): void {
    $read = TheChoicesAsKept::read(Shape::One, Unsealed::of($written));

    expect(iterator_to_array($read->switchedOff(), preserve_keys: false))->toBe([])
        ->and($read->floor())->toBe(Severity::Advisory);
})->with([
    'not a choice at all' => ['"off"'],
    'nothing written' => ['{}'],
    'a kind and a floor this build has no word for' => ['{"kinds_off":["gossip",7],"problems_from":"mild"}'],
    'the wrong shapes' => ['{"kinds_off":"update","problems_from":3}'],
    'not json' => ['{'],
]);

it('keeps the kinds it can read beside one it cannot', function (): void {
    expect(iterator_to_array(TheChoicesAsKept::read(Shape::One, Unsealed::of('{"kinds_off":["gossip","backup"],"problems_from":"critical"}'))->switchedOff(), preserve_keys: false))
        ->toBe([KindOfNotification::Backup]);
});
