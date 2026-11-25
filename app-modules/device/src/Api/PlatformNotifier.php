<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Telling as Centre;
use Lemonfiber\Native\Told;
use Lemonfiber\Native\WhatTheOperatorSaid;
use Lemonfiber\Native\WhyNothingWasTold;
use Modules\Device\Internal\Words;
use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhyNothingIsShown;

use function sprintf;

/**
 * The operator's own notification centre, reached through lemonfiber's bridge.
 *
 * Local rather than pushed, and that is the whole reason this file exists as an
 * adapter instead of a subscription: a push payload travels through Google's or
 * Apple's relay, which would put what a stack said about somebody's home in
 * front of a third party. A local notification is composed and displayed on the
 * handset and never leaves it.
 *
 * **The words come from the translator, keyed by the type.** A
 * {@see Notification} carries a code and nothing else sayable, so there is no
 * sentence to pass through — this looks up two keys and fills them in. The
 * words live in the catalogue, and a notification may carry no credential and
 * no household member's name; the same design serves both.
 *
 * **Why our own bridge rather than the marketplace plugin.** Two reasons, both
 * of them things a screen has to say a different sentence about. The plugin
 * offered `requestPermission()` and nothing that reads the standing answer, so
 * not re-asking somebody who had already declined meant reading that answer
 * through a different package's push facade — which answers nothing at all on
 * Android, because no handler for it is registered there. And it reported a withheld notification as `false`, so a
 * channel the operator switched off, a platform that declined and a permission
 * nobody granted all reached this class as the same value.
 */
final readonly class PlatformNotifier implements Notifier
{
    public function __construct(private Centre $centre, private Words $words, private DeviceAuth $lock) {}

    public function standing(): Asked
    {
        return $this->means($this->centre->standing());
    }

    public function ask(): Asked
    {
        $standing = $this->standing();

        // Guarded rather than trusted. A declined permission is not asked for
        // again automatically, and the platform usually suppresses a second
        // dialog on its own — usually, and not on Android after a single
        // decline. A rule kept by the operating system's good manners is not
        // kept.
        if (! $standing->mayAsk()) {
            return $standing;
        }

        return $this->means($this->centre->ask());
    }

    public function show(Notification $notification): Shown
    {
        if (! $this->standing()->mayProceed()) {
            return Shown::withheld(WhyNothingIsShown::NotificationsAreNotPermitted);
        }

        return $this->whileTheLockStands($notification)->either(
            plain: fn(?WhatTheCoreDecided $says, StackName $called): Told => $this->centre->show(
                $this->idFor($notification),
                $this->words->for(sprintf('notifications.plain.%s_title', $notification->worded()->value), ['stack' => $called->shown()]),
                $this->words->for(sprintf('notifications.plain.%s_body', $notification->worded()->value), ['code' => $says?->shown() ?? '']),
                $notification->leadsTo()->asALink(),
            ),
            guarded: fn(): Told => $this->centre->show(
                $this->idFor($notification),
                $this->words->for(sprintf('notifications.guarded.%s_title', $notification->worded()->value)),
                $this->words->for(sprintf('notifications.guarded.%s_body', $notification->worded()->value)),
                $notification->leadsTo()->asALink(),
            ),
        )->either(
            done: static fn(): Shown => Shown::delivered(),
            withheld: fn(WhyNothingWasTold $why): Shown => Shown::withheld($this->meaning($why)),
        );
    }

    /**
     * The notification as it may be shown right now.
     *
     * Asked of the device, which counts a time away that has not ended: a
     * notification shown once the app has been away longer than the operator
     * allows is shown while the lock stands, and says nothing of the stack.
     */
    private function whileTheLockStands(Notification $notification): Notification
    {
        return $this->lock->standing()->either(
            held: static fn(): Notification => $notification->whileLocked(),
            open: static fn(): Notification => $notification,
        );
    }

    /**
     * What the bridge's three words mean in the terms this application reasons in.
     *
     * A `match` with no default arm, so a fourth word added to the bridge fails
     * here by name rather than falling into whichever case was written last.
     */
    private function means(WhatTheOperatorSaid $said): Asked
    {
        return match ($said) {
            WhatTheOperatorSaid::Granted => Asked::Granted,
            WhatTheOperatorSaid::Denied => Asked::Declined,
            WhatTheOperatorSaid::NotDetermined => Asked::NotYet,
        };
    }

    /**
     * The same, for every way the bridge can say a notification was withheld.
     *
     * Five words become two cases here rather than at each call site. A
     * channel the operator switched off and a permission nobody granted are one
     * sentence on a screen — this application's alerts are off, and here is
     * where to turn them on — and the platform declining outright is another,
     * because asking anybody anything would not change it.
     */
    private function meaning(WhyNothingWasTold $why): WhyNothingIsShown
    {
        return match ($why) {
            WhyNothingWasTold::NotPermitted,
            WhyNothingWasTold::NoSuchChannel => WhyNothingIsShown::NotificationsAreNotPermitted,
            WhyNothingWasTold::TheTimeHasPassed,
            WhyNothingWasTold::NoSuchRepeat,
            WhyNothingWasTold::TheDeviceRefused => WhyNothingIsShown::TheDeviceWouldNotShowIt,
        };
    }

    /**
     * The id the bridge keys it by.
     *
     * An id that collides replaces the notification already showing. One per
     * alert of one stack ({@see Notification::filedAs()}) means its resolution
     * replaces its onset rather than stacking, while two alerts, or one name
     * from two stacks, never overwrite each other.
     */
    private function idFor(Notification $notification): string
    {
        return $notification->filedAs();
    }
}
