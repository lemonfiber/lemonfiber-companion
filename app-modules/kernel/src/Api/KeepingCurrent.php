<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack where it stands on being up to date, and telling it to take one.
 *
 * Reading and acting are one port rather than two, for the reason
 * {@see Supervising} gives: they are one conversation, and a port that only
 * read would leave whoever built the acting half free to reach a client of
 * their own — which is what going through the SDK exists to prevent.
 *
 * **It takes a stack and a session rather than a client**, so that the
 * separate sessions and the pinning cannot be paired up wrongly by a
 * caller.
 *
 * **Taking an update takes a {@see TakingAnUpdate}, which only a reading that
 * offered one can produce.** {@see TakingAnUpdate::offeredBy()} refuses an
 * {@see Upkeep} whose pins said nothing would move, so an update the stack did
 * not report cannot reach this port — and the confirmation names the services
 * the offer carries, which are the ones sent.
 */
interface KeepingCurrent
{
    /**
     * Read where the stack stands, or come away with a reason.
     *
     * Answers {@see WhatIsCurrent} rather than raising, which `C1` requires and
     * an obstacle builds on.
     */
    public function standing(Stack $stack, Session $session): WhatIsCurrent;

    /**
     * Take the update the operator agreed to, or come away with a reason.
     *
     * Answers {@see Underway} — the value a verb and a repair both answer with
     * — because it is the same shape of act: the stack takes the work on and
     * hands back something to follow it by.
     */
    public function take(Stack $stack, Session $session, TakingAnUpdate $agreed): Underway;

    /**
     * What became of an update taken, by the handle taking it answered.
     *
     * Answers {@see HowTheUpdateIsGoing}, whose finished arm is the update's
     * own report: the one answer that says how each service took it.
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheUpdateIsGoing;
}
