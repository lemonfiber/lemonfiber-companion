<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

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

    /**
     * The key for what this permission is for, in the app's own words (`N4-R2`).
     *
     * **Derived rather than written, and that is the whole point of it being
     * here.** The key was spelled twice — once by `PermissionsAreExplainedTest`,
     * which builds it to check the catalogue holds a line, and once by whoever
     * reads the line. The second spelling is a literal in a method body: nothing
     * searches for it from the catalogue side, nothing notices when it is wrong,
     * and a mistyped key renders as the key itself on somebody's screen.
     *
     * With it here there is one spelling, and the arch test that proves every
     * case has a sentence in every locale is proving it about *this* string.
     * A reader who wants the line asks the case for it.
     */
    public function reason(): string
    {
        return sprintf('device.%s_reason', $this->value);
    }

    /**
     * The key for what still works without it (`N4-R3`).
     *
     * Only meaningful where {@see self::hasAnAlternative()} is true, and it does
     * not guard against being asked otherwise: a case answering false would have
     * no line in the catalogue, so the key comes back unresolved and says so on
     * the screen rather than silently reading as something else. The guard that
     * matters is the arch test, which refuses a case claiming an alternative it
     * never names.
     */
    public function alternative(): string
    {
        return sprintf('device.%s_alternative', $this->value);
    }
}
