<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\TheSettingsHeld;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Lock;
use Modules\Kernel\Api\LockAfter;
use Modules\Kernel\Api\Sealed;

/**
 * How long the app may be away before the lock asks again, as the operator chose.
 *
 * Kept sealed, like everything the phone keeps, and told to the device, which
 * measures the time away. Until the operator chooses it is immediately, and a
 * phone that can keep nothing forgets the choice when the app closes.
 */
final readonly class LockingAfter
{
    private TheSettingsHeld $settings;

    public function __construct(Sealed $seal, SettingsKept $kept, private DeviceAuth $device, Clock $clock)
    {
        $this->settings = new TheSettingsHeld($seal, $kept, $clock);
    }

    /** What the operator chose, or immediately. */
    public function current(): LockAfter
    {
        return $this->settings->current()->lockAfter;
    }

    /** Keep the operator's choice, tell the device, and answer what is now in force. */
    public function choose(LockAfter $after): LockAfter
    {
        $this->settings->keep($this->settings->current()->lockingAfter($after));
        $this->device->allowAway($after->howLong());

        return $after;
    }

    /** Tell the device what the operator chose, as the app opens past the lock. */
    public function toldTheDevice(): Lock
    {
        return $this->device->allowAway($this->settings->current()->lockAfter->howLong());
    }
}
