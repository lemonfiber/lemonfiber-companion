<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack where it stands on being up to date, and telling it to take one.
 *
 * Reading and acting are one port rather than two, for the reason
 * {@see Supervising} gives: they are one conversation, and a port that only
 * read would leave whoever built the acting half free to reach a client of
 * their own — which is what `N1-R16` exists to prevent.
 *
 * **It takes a stack and a session rather than a client**, so that `N1-R11`'s
 * separate sessions and `N1-R19`'s pinning cannot be paired up wrongly by a
 * caller.
 *
 * **Taking an update takes a {@see TakingAnUpdate}, which rendering cannot
 * produce.** `N2-R17` wants the confirmation to name the services an update
 * would change, and the way that requirement is broken is never deliberate: a
 * screen draws the pending release, the button is right there, and a tap
 * handler calls the thing that applies it. Nothing in the code says *this was
 * confirmed*, because nothing had to.
 */
interface KeepingCurrent
{
    /**
     * Read where the stack stands, or come away with a reason.
     *
     * Answers {@see WhatIsCurrent} rather than raising, which `C1` requires and
     * `N1-R10` builds on.
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
}
