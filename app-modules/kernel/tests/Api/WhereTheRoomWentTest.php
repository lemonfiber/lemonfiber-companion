<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ALineOfTheAccount;
use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\AVolume;
use Modules\Kernel\Api\HowFreshAReadingIs;
use Modules\Kernel\Api\TheAccount;
use Modules\Kernel\Api\TheDownloadsOnDisk;
use Modules\Kernel\Api\TheVolumes;
use Modules\Kernel\Api\WhatALineIsAbout;
use Modules\Kernel\Api\WhatAVolumeHolds;
use Modules\Kernel\Api\WhatGettingItBackCosts;
use Modules\Kernel\Api\WhatItOccupies;
use Modules\Kernel\Api\WhereTheRoomStands;
use Modules\Kernel\Api\WhereTheRoomWent;

it('hands back everything one reading said, each list in the order given', function (): void {
    $volume = AVolume::measured(WhatAVolumeHolds::Data, '/srv', AnAmountOfRoom::unread(), AnAmountOfRoom::unread(), 0, AnAmountOfRoom::unread(), WhereTheRoomStands::Unknown, HowFreshAReadingIs::live());
    $line = ALineOfTheAccount::for(WhatALineIsAbout::Landing, WhatItOccupies::counted(1, 1), WhatGettingItBackCosts::InProgress);
    $first = ADownloadOnDisk::neverImported('a', 1);
    $second = ADownloadOnDisk::leftAlone('b', 2);

    $room = WhereTheRoomWent::measured(TheVolumes::of($volume), WhereTheRoomStands::Critical, TheAccount::of($line), TheDownloadsOnDisk::of($first, $second), halted: true);

    expect(iterator_to_array($room->volumes(), preserve_keys: false))->toBe([$volume])
        ->and($room->stands())->toBe(WhereTheRoomStands::Critical)
        ->and($room->isHalted())->toBeTrue()
        ->and(iterator_to_array($room->account(), preserve_keys: false))->toBe([$line])
        ->and(iterator_to_array($room->downloads(), preserve_keys: false))->toBe([$first, $second])
        ->and([count($room->volumes()), count($room->account()), count($room->downloads())])->toBe([1, 1, 2]);
});
