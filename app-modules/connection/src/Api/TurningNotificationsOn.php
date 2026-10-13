<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\TheSettingsHeld;
use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Notifying;
use Modules\Kernel\Api\Sealed;

/**
 * Whether the operator turned notifications on, and the one place the phone is asked for them.
 *
 * Turning them on asks the phone, where it may still be asked, and keeps the
 * choice only where the phone said yes, so what is kept is never on while the
 * phone would show nothing. The screen says why before it calls this; nothing
 * else in the app asks.
 */
final readonly class TurningNotificationsOn
{
    private TheSettingsHeld $settings;

    public function __construct(Sealed $seal, SettingsKept $kept, private Notifier $notifier, Clock $clock)
    {
        $this->settings = new TheSettingsHeld($seal, $kept, $clock);
    }

    /** What the operator chose, or off. */
    public function current(): Notifying
    {
        return $this->settings->current()->notifying;
    }

    /** What the phone has said about notifications, read without asking. */
    public function standing(): Asked
    {
        return $this->notifier->standing();
    }

    /** Ask the phone where it may still be asked, keep notifications on where it said yes, and answer what it said. */
    public function turnOn(): Asked
    {
        $asked = $this->notifier->ask();

        if ($asked->mayProceed()) {
            $this->settings->keep($this->settings->current()->notifying(Notifying::On));
        }

        return $asked;
    }

    /** Turn notifications off, and answer what is now in force. */
    public function turnOff(): Notifying
    {
        $this->settings->keep($this->settings->current()->notifying(Notifying::Off));

        return Notifying::Off;
    }
}
