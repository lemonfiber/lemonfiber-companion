<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Household\Internal\Playing\PutsAChannelOnScreen;
use Modules\Household\Internal\Playing\TheChannelOnScreen;
use Modules\Household\Internal\Playing\WhatIsPlaying;
use Modules\Household\Internal\TheLiveTvShelf;
use Modules\Kernel\Api\Playing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\Tuning;
use Tests\Support\Fakes\APlayerOnAHandset;
use Tests\Support\Fakes\ATunerThatAnswered;
use Tests\Support\Fakes\AZoneThatIsSet;

/**
 * Home's Live TV shelf, put together over fakes: a tuner, the keychain it signs in from, a handset's player and a clock set to UTC.
 */
final readonly class TheChannelsOnScreen
{
    /** A shelf over a house with no live TV. */
    public static function none(SecureStorage $storage): TheLiveTvShelf
    {
        return self::over(ATunerThatAnswered::listing(TheChannels::these()), $storage);
    }

    public static function over(Tuning $tuning, SecureStorage $storage, ?Playing $player = null, ?TheChannelOnScreen $channel = null, ?WhatIsPlaying $titles = null): TheLiveTvShelf
    {
        $channel ??= new TheChannelOnScreen();

        return new TheLiveTvShelf(
            $storage,
            $tuning,
            new PutsAChannelOnScreen($storage, $tuning, $player ?? APlayerOnAHandset::working(), $channel, $titles ?? new WhatIsPlaying()),
            $channel,
            AZoneThatIsSet::to('UTC'),
        );
    }
}
