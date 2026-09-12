<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Something the platform will not let the app do until somebody says yes.
 *
 * Named here rather than in `device`, because what the app *needs* is a fact
 * about the app and what the platform *calls* it is a fact about the platform.
 * An adapter translates; a screen explaining why the app is asking (`N4-R2`)
 * needs the concept, not the Android string.
 *
 * **A closed set**, which is what makes it an enum: the app asks for what it
 * asks for, and a permission it has no use for is a permission it must not
 * request. `N4-R1` puts the request at the point of first use, so every case
 * here has exactly one place that asks.
 */
enum Permission: string
{
    /**
     * Reaching a stack on the same network.
     *
     * The one the whole app depends on, and the one whose refusal looks most
     * like something else — `Obstacle::LocalNetworkIsNotPermitted` exists so
     * that a refusal is not reported as a stack that is switched off.
     */
    case LocalNetwork = 'local_network';

    /** Showing a notification the core decided to send (`N4-R11`). */
    case Notifications = 'notifications';

    /** Reading a pairing code with the camera. */
    case Camera = 'camera';

    /**
     * Whether the app still works with this one declined.
     *
     * `N4-R3` requires every permission to be optional with a working
     * alternative, and this is where "working alternative" stops being a
     * promise in a document. All three are true, and each has a different
     * alternative: a pairing code can be typed instead of scanned, the app can
     * be opened to see what a notification would have said, and a stack can be
     * reached over a route the platform does not gate.
     *
     * A case answering false would be a permission this app cannot honestly
     * call optional, and it is here so that adding one is a decision somebody
     * makes in front of `N4-R3` rather than by writing a new case.
     */
    public function hasAnAlternative(): bool
    {
        return match ($this) {
            self::LocalNetwork, self::Notifications, self::Camera => true,
        };
    }
}
