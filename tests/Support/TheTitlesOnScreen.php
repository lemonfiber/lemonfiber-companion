<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Connection\Api\TheGrantForThisDevice;
use Modules\Household\Internal\Playing\PutsATitleOnScreen;
use Modules\Household\Internal\Playing\WhatIsPlaying;
use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Granting;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Playing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Watching;

use function str_repeat;

use Tests\Support\Fakes\ADeviceWithAnId;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\Fakes\AStackThatGrants;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\GrantsKeptInMemory;

/**
 * Play, put together over fakes: the shelf a screen reads, the keychain it
 * signs in from, a core that grants and a handset's player.
 */
final readonly class TheTitlesOnScreen
{
    /** A grant that has not lapsed by the moment these run at. */
    public static function aGrant(): AGrant
    {
        return AGrant::of(str_repeat('0b', 16), Instant::atEpochSeconds(2_000_000_000));
    }

    /** Play over these, granting {@see AGrant()} unless a core is given. */
    public static function over(
        Watching $watching,
        SecureStorage $storage,
        ?Playing $player = null,
        ?WhatIsPlaying $playing = null,
        ?Granting $core = null,
    ): PutsATitleOnScreen {
        return new PutsATitleOnScreen(
            $storage,
            $watching,
            new TheGrantForThisDevice(
                $core ?? AStackThatGrants::granting(self::aGrant()),
                GrantsKeptInMemory::working(),
                ADeviceWithAnId::named('this-device'),
                FrozenClock::at(Instant::atEpochSeconds(1_000_000_000)),
            ),
            $player ?? APlayerOnAHandset::working(),
            $playing ?? new WhatIsPlaying(),
        );
    }
}
