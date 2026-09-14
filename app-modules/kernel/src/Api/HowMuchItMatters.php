<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How much of the stack stops working if this service does.
 *
 * An enum for `D4`'s reason — `status.services[].criticality` is exactly these
 * five — and carried rather than dropped because it is the whole of what makes
 * a list of nineteen services readable. Without it every row is equally loud,
 * and the operator scanning for the one that matters reads all nineteen.
 *
 * **It is not a severity and not a state.** {@see Severity} says how much a
 * *finding* costs and comes from a check that decided something;
 * {@see HowAServiceRuns} says where a service stands right now. This says what
 * it would cost if that went wrong, which is a property of the machine's design
 * rather than of this moment — a stopped `optional` service and a stopped
 * `critical` one are the same state and two very different afternoons.
 *
 * **Declared worst first**, which is the contract's order and the order a
 * screen reads in. The position is meaning, as it is on {@see HowAServiceRuns}.
 */
enum HowMuchItMatters: string
{
    /** Nothing else works without it. */
    case Critical = 'critical';

    /** The stack's own machinery; things break in ways that look unrelated. */
    case Core = 'core';

    /** Something the household would notice within the day. */
    case Important = 'important';

    /** Something they would notice eventually, and live without. */
    case Enhancing = 'enhancing';

    /** Nobody would notice. */
    case Optional = 'optional';

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()}.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.matters.%s', $this->value);
    }

    /**
     * Whether stopping this is the kind of thing to say twice about (`N2-R8`).
     *
     * The line is drawn once, here, rather than at each screen that offers a
     * stop. `N2-R8` wants a disruptive action to state what it disturbs before
     * it is confirmed, and *disruptive* is not a property of the verb: stopping
     * an optional service disturbs nobody, and stopping a critical one takes
     * the house's evening with it.
     *
     * `Core` is included with `Critical` deliberately. A core service is the
     * stack's own machinery, and what it takes down when it goes is other
     * things — which an operator reads as unrelated breakage, and is the worst
     * kind of surprise to have agreed to without being told.
     */
    public function stoppingItDisturbsTheHouse(): bool
    {
        return match ($this) {
            self::Critical, self::Core => true,
            self::Important, self::Enhancing, self::Optional => false,
        };
    }
}
