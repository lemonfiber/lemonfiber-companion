<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\AVolume;
use Modules\Kernel\Api\HowFreshAReadingIs;
use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\WhatAVolumeHolds;
use Modules\Kernel\Api\WhereTheRoomStands;

it('keeps every figure it was measured with', function (): void {
    $free = AnAmountOfRoom::of(10, 'free');
    $limit = AnAmountOfRoom::of(100, 'limit');
    $projected = AnAmountOfRoom::of(5, 'projected');
    $reading = HowFreshAReadingIs::live();
    $volume = AVolume::measured(WhatAVolumeHolds::Services, '/var', $free, $limit, 0, $projected, WhereTheRoomStands::Advisory, $reading);

    expect([$volume->holds(), $volume->point(), $volume->free(), $volume->limit(), $volume->committed(), $volume->projected(), $volume->stands(), $volume->reading()])
        ->toBe([WhatAVolumeHolds::Services, '/var', $free, $limit, 0, $projected, WhereTheRoomStands::Advisory, $reading]);
});

it('refuses what is on its way below nothing, and takes nothing on its way', function (): void {
    $make = static fn(int $committed): AVolume => AVolume::measured(WhatAVolumeHolds::Data, '', AnAmountOfRoom::unread(), AnAmountOfRoom::unread(), $committed, AnAmountOfRoom::unread(), WhereTheRoomStands::Unknown, HowFreshAReadingIs::live());

    expect(fn(): AVolume => $make(-1))->toThrow(RoomSaysNothing::class, '`committed`')
        ->and($make(0)->committed())->toBe(0);
});
