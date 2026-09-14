<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where one of a stack's services stands right now.
 *
 * An enum, because the set is closed by the contract rather than by this app —
 * `status.services[].state` lists exactly these nine, so `D4` gets its enum and
 * a tenth would be a contract change rather than a value to pass through.
 *
 * **Nine, and the distinctions are the point.** `Stopped` and `Failed` are the
 * pair an operator most needs told apart: one was turned off and the other
 * fell over, and a screen that drew them the same way would have somebody
 * restarting a service that is off on purpose while a crashed one sits beside
 * it. `CrashLooping` is a third thing again — it is *starting*, repeatedly,
 * which is the state a restart makes worse.
 *
 * **`HostManaged` is the one that is nobody's business here.** A service the
 * host runs is not one this stack starts or stops, and offering a button for it
 * would be the app promising something the machine will refuse — `N1-R3` says
 * an action is offered and the failure reported, and this is the narrow case
 * where there is no action to offer in the first place, because the control
 * does not exist rather than being temporarily out of reach.
 *
 * **Declared in the order the contract lists them**, worst first. The position
 * is meaning: a screen sorting services alphabetically puts a crashed one
 * under a healthy one, and the operator scrolls past the row they opened the
 * app for.
 */
enum HowAServiceRuns: string
{
    /** It fell over. */
    case Failed = 'failed';

    /** It keeps falling over and being started again, which a restart worsens. */
    case CrashLooping = 'crash-looping';

    /** It is up and answering badly. */
    case Unhealthy = 'unhealthy';

    /** The stack expected it and it is not there at all. */
    case Absent = 'absent';

    /** Turned off, which is a decision somebody made rather than a fault. */
    case Stopped = 'stopped';

    /** On its way up. */
    case Starting = 'starting';

    /** Up, with nothing saying whether it is well. */
    case Running = 'running';

    /** Up, and answering as it should. */
    case Healthy = 'healthy';

    /** The host runs it, so this stack does not start or stop it. */
    case HostManaged = 'host-managed';

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()} for the
     * argument. The hyphen in a value is carried into the key rather than
     * smoothed out, as {@see Stage} and {@see Waiting} already do.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.service.%s', $this->value);
    }

    /**
     * Whether this stack is the thing that starts and stops it (`N2-R7`).
     *
     * The one decision that belongs here rather than on a screen. `N2-R7` asks
     * the app to offer start, stop and restart — and a service the host runs
     * has no such control to offer, which is not the same as a control that is
     * temporarily out of reach. A screen working this out for itself would be a
     * second copy of where the stack's authority ends.
     */
    public function isThisStacksToRun(): bool
    {
        return $this !== self::HostManaged;
    }

    /**
     * Whether this will become something else without anybody touching it.
     *
     * The one state that resolves on its own, which is what `N1-R27` wants a
     * stated cadence for: a service that is starting becomes a running one in a
     * few seconds, and a screen showing *starting* with no way to learn
     * otherwise leaves somebody tapping to find out. Every other case here is a
     * standing answer — a stopped service stays stopped until somebody says
     * otherwise, and a failed one until somebody does something about it.
     */
    public function isSettling(): bool
    {
        return $this === self::Starting;
    }

    /**
     * Whether restarting it now would make things worse.
     *
     * `CrashLooping` is already being started over and over; asking for another
     * restart adds a start to a queue of starts. `N2-R8` has a disruptive
     * action state what it disturbs before it is confirmed, and this is the
     * case where the honest statement is *this will not help*.
     */
    public function isAlreadyBeingRestarted(): bool
    {
        return $this === self::CrashLooping;
    }
}
