<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what putting its configuration back would revert, telling it
 * to, and asking what became of either.
 *
 * One conversation, so one port, for the reason {@see KeepingCurrent} gives.
 *
 * **Both halves are work the stack names.** Unconfirmed, `reset` compares the
 * operator's files and connections with lemonfiber's own and changes nothing;
 * confirmed, it writes lemonfiber's back. The stack answers each with a job,
 * so each answers {@see Underway} here and the report is asked after by the
 * handle.
 *
 * **Reverting takes an {@see AResetAgreed}, which only a preview produces.** A
 * reset nobody was shown cannot reach this port, and a preview that would
 * revert nothing offers nothing to agree to.
 */
interface ResettingTheConfiguration
{
    /**
     * Ask what putting the configuration back would revert, changing nothing.
     *
     * Answers {@see Underway} rather than the preview: the stack names the work,
     * and the preview is read by the handle.
     */
    public function wouldRevert(Stack $stack, Session $session): Underway;

    /**
     * Put the configuration back, having been shown what that reverts.
     *
     * Answers {@see Underway}, like asking what it would revert.
     */
    public function revert(Stack $stack, Session $session, AResetAgreed $agreed): Underway;

    /**
     * What became of either, by the handle it answered.
     *
     * Answers {@see HowTheResetIsGoing} rather than raising. Which of the two
     * a finished report is, is the stack's `confirmed`, carried on
     * {@see TheReset} rather than remembered from what was asked.
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheResetIsGoing;
}
