<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Connection\Api\LockingAfter;
use Modules\Kernel\Api\Instant;
use Modules\Operator\Internal\Screens\Locked;
use Tests\Support\Fakes\ADeviceThatKnowsYou;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ConnectionSettingsInMemory;
use Tests\Support\Fakes\FrozenClock;

/** The lock screen over a device, with nothing chosen for how long the app may be away. */
final readonly class ALockScreen
{
    public static function over(ADeviceThatKnowsYou $device): Locked
    {
        return new Locked(
            $device,
            new LockingAfter(
                ASealInMemory::working(),
                ConnectionSettingsInMemory::empty(),
                $device,
                FrozenClock::at(Instant::atEpochSeconds(0)),
            ),
        );
    }
}
