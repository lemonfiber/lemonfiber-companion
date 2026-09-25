<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\HowMuchRoomAVolumeHas;
use Modules\Kernel\Api\RoomSaysNothing;

it('keeps every figure it was counted with', function (): void {
    $free = AnAmountOfRoom::of(10, 'free');
    $limit = AnAmountOfRoom::of(100, 'limit');
    $projected = AnAmountOfRoom::unread();
    $room = HowMuchRoomAVolumeHas::counted($free, $limit, 7, $projected);

    expect([$room->free(), $room->limit(), $room->committed(), $room->projected()])->toBe([$free, $limit, 7, $projected]);
});

it('refuses what is on its way below nothing, and takes nothing on its way', function (): void {
    $make = static fn(int $committed): HowMuchRoomAVolumeHas => HowMuchRoomAVolumeHas::counted(AnAmountOfRoom::unread(), AnAmountOfRoom::unread(), $committed, AnAmountOfRoom::unread());

    expect(fn(): HowMuchRoomAVolumeHas => $make(-1))->toThrow(RoomSaysNothing::class, '`committed`')
        ->and($make(0)->committed())->toBe(0);
});
