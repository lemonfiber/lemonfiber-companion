<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What the whole stack amounts to, as the machine itself judges it.
 *
 * An enum for `D4`'s reason — `status.condition` is exactly these four.
 *
 * **It is the stack's judgement, not a sum this app works out.** The same
 * argument {@see Overall} makes about a diagnostic run: a screen adding up the
 * services and deciding for itself would be a second opinion about a judgement
 * the machine already made, and the two would disagree the first time the
 * machine weighed something differently from the arithmetic here.
 *
 * **It is not {@see Overall}.** That one is what the *checks* concluded and
 * comes from a doctor run; this is what is *running* and comes from the status
 * read. A stack can pass every check while half its services are stopped —
 * somebody turned them off — and it can be `active` with a finding against it.
 * Folding the two would make one of those states unsayable.
 *
 * Declared worst first, which is the contract's order and the order a screen
 * reads in.
 */
enum HowTheStackIsRunning: string
{
    /** Nothing is running. */
    case Inactive = 'inactive';

    /** Things are running and something is wrong with them. */
    case Degraded = 'degraded';

    /** Some of what should be running is. */
    case Partial = 'partial';

    /** All of it, as it should be. */
    case Active = 'active';

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is the shape every word in this app reaches
     * the catalogue by — see {@see Conclusion::saidOnTheScreen()}.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.running.%s', $this->value);
    }

    /**
     * Whether everything the stack expects to be running is.
     *
     * One decision in one place, for the reason {@see Severity::demandsAttention()}
     * gives: two screens deciding what counts as *fine* is how an operator
     * learns that one of them is lying.
     */
    public function isAllOfIt(): bool
    {
        return $this === self::Active;
    }
}
