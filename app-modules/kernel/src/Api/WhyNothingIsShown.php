<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why a notification the core decided on was not put in front of anybody.
 *
 * Both cases are ordinary states of the world rather than faults, which is why
 * they are values and not exceptions: an operator who has not granted
 * notification permission is not a bug, and neither is a notification arriving
 * for a stack they removed a second ago.
 *
 * Naming them apart is the point. "Not shown" is one word for two situations
 * with nothing in common — one is answered by asking the operator for
 * permission, the other by dropping the notification and never asking anybody
 * anything. A single boolean would have put both behind the same screen.
 */
enum WhyNothingIsShown
{
    /**
     * The operator has not allowed notifications, or has withdrawn that.
     *
     * Two rules underneath: the prompt belongs at the
     * point of first use rather than on launch, and a declined permission is
     * not asked for again automatically. A notification withheld for this
     * reason is one the app may *offer* to ask about, on a screen, in front of
     * somebody — it is not one to retry, and it is not a reason to prompt.
     *
     * Which of those two applies is {@see Asked}'s to answer, and the
     * difference matters: never asked and declined are the same to a caller
     * wanting to show something and opposite to one deciding whether to ask.
     */
    case NotificationsAreNotPermitted;

    /**
     * The notification is about a stack this device no longer has.
     *
     * a stack no longer configured. Not rare — it is what happens whenever a removal and an
     * in-flight alert cross — and the only correct handling is to drop it
     * silently. Telling somebody about a machine they just removed is worse
     * than saying nothing.
     */
    case TheStackIsGone;

    /**
     * Whether asking the operator could change this answer.
     *
     * The one question a caller actually has, answered here rather than by a
     * `match` at each call site — where the arm somebody forgets to add is the
     * one that offers to re-ask about a stack that no longer exists.
     */
    public function mightBeWorthAsking(): bool
    {
        return $this === self::NotificationsAreNotPermitted;
    }
}
