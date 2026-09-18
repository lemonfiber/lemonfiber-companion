<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what has stopped coming in.
 *
 * Four things must each be reachable, and this is the first
 * of them: stuck downloads. Until this existed the app could tell an operator
 * their machine was healthy while four titles the house had asked for sat at a
 * stage nothing was going to move them past — a stack passing every check and a
 * household getting nothing are not a contradiction, which is exactly why the
 * requirement lists this separately from health.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives: each stack's session is separate and
 * every connection is pinned against that stack's fingerprint, so a port
 * taking a client would let a caller pair the two up wrongly. This signature
 * makes that mistake unspellable.
 *
 * **One method, which is one-read-per-frame on this side.** A screen asks once and
 * renders what came back; it does not poll and it does not ask again to fill in
 * a column. A port with a method per stage would be a screen opening ten
 * connections to a machine on a home network to draw one list.
 */
interface Stalling
{
    /**
     * Ask a stack what has stopped, or come away with a reason.
     *
     * Answers {@see WhatIsStuck} rather than raising, which `C1` requires: a
     * stack that is asleep, one on another network and one whose session has
     * ended are ordinary states of the world, and an operator is
     * told which of them they met.
     */
    public function stoppedOn(Stack $stack, Session $session): WhatIsStuck;
}
