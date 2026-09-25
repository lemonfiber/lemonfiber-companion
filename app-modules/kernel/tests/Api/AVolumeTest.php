<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\AVolume;
use Modules\Kernel\Api\HowFreshAReadingIs;
use Modules\Kernel\Api\HowMuchRoomAVolumeHas;
use Modules\Kernel\Api\WhatAVolumeHolds;
use Modules\Kernel\Api\WhereTheRoomStands;

it('keeps which volume it is, where it is mounted, its figures, where it stands and how fresh the reading is', function (): void {
    $room = HowMuchRoomAVolumeHas::counted(AnAmountOfRoom::of(10, 'free'), AnAmountOfRoom::of(100, 'limit'), 0, AnAmountOfRoom::of(5, 'projected'));
    $reading = HowFreshAReadingIs::live();
    $volume = AVolume::measured(WhatAVolumeHolds::Services, '/var', $room, WhereTheRoomStands::Advisory, $reading);

    expect([$volume->holds(), $volume->point(), $volume->room(), $volume->stands(), $volume->reading()])
        ->toBe([WhatAVolumeHolds::Services, '/var', $room, WhereTheRoomStands::Advisory, $reading]);
});
