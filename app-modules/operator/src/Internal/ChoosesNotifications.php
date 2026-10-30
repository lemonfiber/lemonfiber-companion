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

    /** Where asking for notifications stands, while the reason for them is open. */
    public ?WhereAskingForNotificationsStands $askingForNotifications = null;

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

    /**
     * The switch was turned on or off: off turns them off, on opens the reason first.
     *
     * Taken as the state the switch now shows, which the platform hands over,
     * so the switch drawn again after a choice is a choice already made.
     */
    public function notificationsTurned(bool $on): void
    {
        if ($on === $this->notificationsAreOn()) {
            return;
        }

        if (! $on) {
            $this->notifying = $this->notifications->turnOff();

            return;
        }

        $this->askingForNotifications = $this->notifications->standing() === Asked::Declined
            ? WhereAskingForNotificationsStands::Declined
            : WhereAskingForNotificationsStands::Explained;
    }

    /** The operator read the reason and allowed notifications: the phone is asked now, and they are on where it said yes. */
    public function allowNotifications(): void
    {
        $this->askingForNotifications = $this->notifications->turnOn()->mayProceed() ? null : WhereAskingForNotificationsStands::Declined;
        $this->notifying = $this->notifications->current();
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
        $this->askingForNotifications = null;
    }
}
