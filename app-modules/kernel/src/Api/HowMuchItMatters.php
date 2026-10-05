<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What it costs when this service fails, as the stack's catalogue rates it.
 *
 * An enum, because `status.services[].criticality` is exactly these five, and
 * carried rather than dropped because it is the whole of what makes a list of
 * twenty services readable. Without it every row is equally loud, and the
 * operator scanning for the one that matters reads all twenty.
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
    /** Its failure has consequences beyond the stack. */
    case Critical = 'critical';

    /** The stack cannot do its job without it. */
    case Core = 'core';

    /** Without it, a significant capability is lost. */
    case Important = 'important';

    /** Quality of life; the stack does its job without it. */
    case Enhancing = 'enhancing';

    /** Off unless the operator asked for it. */
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
     * Whether stopping this is the kind of thing to say twice about.
     *
     * The line is drawn once, here, rather than at each screen that offers a
     * stop. A disruptive action states what it disturbs before
     * it is confirmed, and *disruptive* is not a property of the verb: stopping
     * an optional service disturbs nobody, and stopping a critical one takes
     * the house's evening with it.
     *
     * `Core` is included with `Critical` deliberately. Without a core service
     * the stack cannot do its job, and what it takes down when it goes is other
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
