<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What happened the last time the app asked for a permission.
 *
 * This is the requirement that needs a record: a declined
 * permission must not be requested again automatically. Without somewhere to
 * write that down, "automatically" is whatever the code path happens to do —
 * and the code path that asks at the point of first use will reach that point
 * again the next time the operator opens the screen.
 *
 * **`NotYet` is not `Declined`.** They are the same to a caller that only wants
 * to know whether it may proceed, and opposite to one deciding whether to ask:
 * never asked means ask, and declined means do not. Collapsing them is exactly
 * how an app ends up prompting somebody every time they open a screen, which is
 * the behaviour this exists to forbid.
 */
enum Asked: string
{
    /** The app has not asked, so the point of first use is still ahead. */
    case NotYet = 'not_yet';

    /** Asked and granted. */
    case Granted = 'granted';

    /** Asked and declined, and not to be asked again by the app. */
    case Declined = 'declined';

    /**
     * Whether the app may raise the system prompt now.
     *
     * The whole of it in one place. `Granted` answers false for a
     * different reason than `Declined` does — there is nothing to ask for —
     * and both are false, which is why this is a method rather than a
     * comparison written at each call site.
     */
    public function mayAsk(): bool
    {
        return match ($this) {
            self::NotYet => true,
            self::Granted, self::Declined => false,
        };
    }

    /**
     * Whether the app may do the thing the permission guards.
     *
     * Deliberately not the negation of {@see self::mayAsk()}. A caller that
     * conflated them would treat "never asked" as "may proceed", which is the
     * platform prompt appearing in the middle of an action rather than before
     * it — the app's own words come first.
     */
    public function mayProceed(): bool
    {
        return $this === self::Granted;
    }
}
