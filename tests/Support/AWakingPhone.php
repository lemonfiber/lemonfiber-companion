<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Connection\Api\ChoosingWhoWakesThisPhone;
use Modules\Connection\Api\WakingThisPhone;
use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\WakingKept;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KeepingThePhoneKeysName;
use Modules\Kernel\Api\ReachingADistributor;
use Modules\Kernel\Api\RegisteringAWake;
use Modules\Kernel\Api\Sealed;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\AStackThatKeepsWakes;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\DistributorsOnAPhone;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\PhoneKeysNamesKeptInMemory;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\Fakes\WakingKeptInMemory;

/** Waking a phone through a distributor, put together from fakes, each one the test may name. */
final readonly class AWakingPhone
{
    /** Whether each stack wakes the phone, over fakes; an iPhone where no distributors are named. */
    public static function waking(
        ?ReachingADistributor $distributors = null,
        ?RegisteringAWake $stack = null,
        ?KeepingThePhoneKeysName $names = null,
        ?WakingKept $kept = null,
        ?Sealed $seal = null,
    ): WakingThisPhone {
        return new WakingThisPhone(
            $seal ?? ASealInMemory::working(),
            $kept ?? WakingKeptInMemory::empty(),
            FrozenClock::at(Instant::atEpochSeconds(0)),
            $distributors ?? DistributorsOnAPhone::notHere(),
            $stack ?? AStackThatKeepsWakes::reachable(),
            $names ?? PhoneKeysNamesKeptInMemory::working(),
            SequencedEntropy::counting(),
        );
    }

    /** Which distributor wakes the phone, over fakes; an iPhone where no distributors are named. */
    public static function choosing(?ReachingADistributor $distributors = null, ?SettingsKept $settings = null): ChoosingWhoWakesThisPhone
    {
        return new ChoosingWhoWakesThisPhone(
            ASealInMemory::working(),
            $settings ?? ConnectionSettingsInMemory::empty(),
            FrozenClock::at(Instant::atEpochSeconds(0)),
            $distributors ?? DistributorsOnAPhone::notHere(),
        );
    }
}
