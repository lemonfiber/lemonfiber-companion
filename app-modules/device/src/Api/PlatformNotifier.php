<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Telling as Centre;
use Lemonfiber\Native\Told;
use Lemonfiber\Native\WhatTheOperatorSaid;
use Lemonfiber\Native\WhyNothingWasTold;
use Modules\Device\Internal\Words;
use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\StackId;
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
 * **The words come from the translator, keyed by the type.** A {@see Notification}
 * carries a code and nothing else sayable, so there is no sentence to pass
 * through — this looks up two keys and fills them in. `L1` wants the words in
 * the catalogue, and a notification may carry no credential and no household
 * member's name; the same design serves both.
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
    /**
     * What a notification's id is prefixed with.
     *
     * The bridge keys everything by a caller-chosen id, and an id that collides
     * replaces the notification already showing. One per code means a second
     * alert about the same thing updates the first rather than stacking —
     * which is what an operator wants — while two different codes never
     * overwrite each other.
     */
    private const string UNDER = 'lemonfiber';

    public function __construct(private Centre $centre, private Words $words) {}

    public function standing(): Asked
    {
        // Reads the answer **without prompting**, which is the whole of it: a
        // declined permission must not be requested again automatically, and a
        // method that asks in order to report cannot keep that promise. The
        // bridge answers this as a separate function from the one that raises
        // the dialog, which is what makes the promise keepable.
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

        // The bridge waits for the operator, so what comes back is what they
        // said rather than what was standing while the dialog was up. Reading
        // it back separately would report a refusal about somebody still
        // looking at the question, because both native halves record that the
        // prompt was raised before raising it.
        return $this->means($this->centre->ask());
    }

    public function show(Notification $notification): Shown
    {
        // Reads, never asks. A notification arriving is not the point of first
        // use — the operator is not looking at the app, and both the prompt and
        // the explanation before it belong somewhere they are.
        if (! $this->standing()->mayProceed()) {
            return Shown::withheld(WhyNothingIsShown::NotificationsAreNotPermitted);
        }

        return $notification->either(
            plain: fn(WhatTheCoreDecided $says, StackId $about): Told => $this->centre->show(
                $this->idFor($says),
                $this->words->for('notifications.plain.title', ['stack' => $about->stored()]),
                $this->words->for('notifications.plain.body', ['code' => $says->shown()]),
            ),
            // No stack reaches this arm, so nothing here can name one. The
            // wording is the guarded pair for the same reason — while the app
            // is locked it must hold on a screen anybody walking past can read.
            guarded: fn(WhatTheCoreDecided $says): Told => $this->centre->show(
                $this->idFor($says),
                $this->words->for('notifications.guarded.title'),
                $this->words->for('notifications.guarded.body'),
            ),
        )->either(
            done: static fn(): Shown => Shown::delivered(),
            withheld: fn(WhyNothingWasTold $why): Shown => Shown::withheld($this->meaning($why)),
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
     * The same, for the three ways a notification can be withheld.
     *
     * Three words become two cases here rather than at each call site. A
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
            WhyNothingWasTold::TheDeviceRefused => WhyNothingIsShown::TheDeviceWouldNotShowIt,
        };
    }

    /** One id per code, so a repeat updates the alert rather than stacking another. */
    private function idFor(WhatTheCoreDecided $says): string
    {
        return sprintf('%s.%s', self::UNDER, $says->shown());
    }
}
