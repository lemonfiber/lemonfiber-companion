<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\TheSettingsHeld;
use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Notifying;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\WhyNothingIsShown;

/**
 * Every notification, but only while the operator has notifications turned on.
 *
 * Read from what the phone keeps on every one, so turning them off in the
 * app's settings holds from the next notification on.
 */
final readonly class NotifyingOnlyWhenTurnedOn implements Notifier
{
    private TheSettingsHeld $settings;

    public function __construct(private Notifier $within, Sealed $seal, SettingsKept $kept, Clock $clock)
    {
        $this->settings = new TheSettingsHeld($seal, $kept, $clock);
    }

    public function standing(): Asked
    {
        return $this->within->standing();
    }

    public function ask(): Asked
    {
        return $this->within->ask();
    }

    public function show(Notification $notification): Shown
    {
        if ($this->settings->current()->notifying !== Notifying::On) {
            return Shown::withheld(WhyNothingIsShown::TheOperatorTurnedThemOff);
        }

        return $this->within->show($notification);
    }
}
