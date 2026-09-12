<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Modules\Device\Internal\Words;
use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhyNothingIsShown;
use Native\Mobile\PushNotifications as Permissions;
use NativePHP\LocalNotifications\LocalNotifications as Platform;

use function sprintf;

/**
 * The operator's own notification centre, reached through the local plugin.
 *
 * Local rather than pushed, and that is the whole reason this file exists as an
 * adapter instead of a subscription: a push payload travels through Google's or
 * Apple's relay, which would put what a stack said about somebody's home in
 * front of a third party. A local notification is composed and displayed on the
 * handset and never leaves it.
 *
 * **The words come from the translator, keyed by the type.** A {@see Notification}
 * carries a {@see Code} and nothing else sayable, so there is no sentence to
 * pass through — this looks up two keys and fills them in. `L1` wants the words
 * in the catalogue; `N4-R10` wants nowhere to smuggle a credential or a name
 * through; the same design serves both.
 *
 * **A notification is sent by letting the builder go out of scope.** The
 * plugin's `ScheduledNotification` fires in `__destruct()` — `execute()` is
 * protected, so there is no other way to send one. That makes an ordinary
 * mistake dangerous in an unusual direction: assigning the builder to a property
 * here, or returning it, would mean nothing is ever shown, and no error would
 * say so. It is built and released inside one statement for that reason, and
 * should stay that way.
 */
final readonly class PlatformNotifier implements Notifier
{
    /**
     * What a notification's id is prefixed with.
     *
     * The plugin keys everything by a caller-chosen id, and an id that collides
     * replaces the notification already showing. One per stack per code means
     * a second alert about the same thing updates the first rather than
     * stacking — which is what an operator wants — while two different stacks
     * never overwrite each other (`N1-R11`).
     */
    private const string UNDER = 'lemonfiber';

    public function __construct(
        private Platform $centre,
        private Permissions $permissions,
        private Words $words,
    ) {}

    public function standing(): Asked
    {
        // `PushNotifications::checkPermission()` reads the answer **without
        // prompting**, which is the call this adapter was missing. The plugin
        // that sends local notifications offers only `requestPermission()`,
        // which asks and answers in one go — so reading the standing answer
        // through it meant asking for it, every time.
        //
        // Push and local share one permission on both platforms — the
        // `UNUserNotificationCenter` authorisation on iOS, `POST_NOTIFICATIONS`
        // on Android 13+ — so the push facade's reader is the right reader for
        // a local notification. That is a fact about the platforms rather than
        // about these two packages, which is why it is written down here.
        return WhatTheDeviceSaid::orNothingSaid($this->permissions->checkPermission())->means();
    }

    public function ask(): Asked
    {
        $standing = $this->standing();

        // Guarded rather than trusted. `N4-R4` says a declined permission is
        // not asked again automatically, and the platform usually suppresses a
        // second dialog on its own — usually, and not on Android after a single
        // decline. A rule kept by the operating system's good manners is not
        // kept.
        if (! $standing->mayAsk()) {
            return $standing;
        }

        $this->centre->requestPermission();

        // Read back rather than believing what the prompt returned. The answer
        // that matters is the one the platform now holds, and on iOS the
        // callback that carries the prompt's own result arrives after this
        // call has returned.
        return $this->standing();
    }

    public function show(Notification $notification): Shown
    {
        // Reads, never asks. A notification arriving is not the point of first
        // use — the operator is not looking at the app, and `N4-R1` and `N4-R2`
        // both want the prompt somewhere they are.
        if (! $this->standing()->mayProceed()) {
            return Shown::withheld(WhyNothingIsShown::NotificationsAreNotPermitted);
        }

        $notification->either(
            plain: function (Code $says, StackId $about): Code {
                // Built and released in one statement: the builder sends when
                // it is destroyed, so anything that keeps it alive stops it.
                $this->centre->send($this->idFor($says))
                    ->title($this->words->for('notifications.plain.title', ['stack' => $about->stored()]))
                    ->body($this->words->for('notifications.plain.body', ['code' => $says->shown()]));

                return $says;
            },
            guarded: function (Code $says): Code {
                // No stack reaches this arm, so nothing here can name one
                // (`N4-R20`). The wording is the guarded pair for the same
                // reason — it must hold on a screen anybody walking past can
                // read.
                $this->centre->send($this->idFor($says))
                    ->title($this->words->for('notifications.guarded.title'))
                    ->body($this->words->for('notifications.guarded.body'));

                return $says;
            },
        );

        return Shown::delivered();
    }

    /** One id per code, so a repeat updates the alert rather than stacking another. */
    private function idFor(Code $says): string
    {
        return sprintf('%s.%s', self::UNDER, $says->shown());
    }
}
