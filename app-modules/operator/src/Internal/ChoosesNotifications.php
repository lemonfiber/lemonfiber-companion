<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Notifying;
use Modules\Kernel\Api\Permission;
use Modules\Wayfinding\Api\WhoTheSettingsSpeakTo;

/**
 * The operator's switch for notifications: off until they turn it on, and the phone asked only after the app says why.
 *
 * Turning it on opens the reason first, which says too that while the app is
 * closed it may tell them late or not at all; only its own button asks the
 * phone. Where the phone was told no before, the reason says where to turn
 * them on instead, with the way to the phone's settings.
 *
 * The screen that uses it takes {@see \Modules\Connection\Api\TurningNotificationsOn}
 * as a protected `$notifications`, and speaks to whoever {@see self::$speaksTo} names.
 */
trait ChoosesNotifications
{
    /** Whether notifications are on, once read. */
    public ?Notifying $notifying = null;

    /** Whether the reason for notifications is open, before the phone is asked. */
    public bool $explainingNotifications = false;

    /** Whether the phone has been told no, so asking again would show nothing. */
    public bool $notificationsDeclined = false;

    /** Whether this screen offers the switch: to the operator, and never in a member's words. */
    public function offersNotifications(): bool
    {
        return $this->speaksTo === WhoTheSettingsSpeakTo::Anyone;
    }

    /** Whether notifications are on. */
    public function notificationsAreOn(): bool
    {
        $this->notifying ??= $this->notifications->current();

        return $this->notifying === Notifying::On;
    }

    /** The operator tapped the switch: off turns them off at once, on opens the reason first. */
    public function toggleNotifications(): void
    {
        if ($this->notificationsAreOn()) {
            $this->notifying = $this->notifications->turnOff();

            return;
        }

        $this->notificationsDeclined = $this->notifications->standing() === Asked::Declined;
        $this->explainingNotifications = true;
    }

    /** The operator read the reason and allowed notifications: the phone is asked now, and they are on where it said yes. */
    public function allowNotifications(): void
    {
        $this->notificationsDeclined = ! $this->notifications->turnOn()->mayProceed();
        $this->notifying = $this->notifications->current();
        $this->explainingNotifications = $this->notificationsDeclined;
    }

    /** Why the app wants notifications, said before the phone is asked. */
    public function whyNotifications(): string
    {
        return Permission::Notifications->reason();
    }

    /** What still works without notifications. */
    public function withoutNotifications(): string
    {
        return Permission::Notifications->alternative();
    }

    /** The operator closed the reason without allowing anything. */
    public function notNow(): void
    {
        $this->explainingNotifications = false;
    }
}
