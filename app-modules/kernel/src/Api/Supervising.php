<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what it is running, and telling it to change that.
 *
 * The app offers start, stop and restart by form and by service.
 * Reading and acting are one port rather than two because they are one
 * conversation: an operator reads a listing, picks a row, and says a verb — and
 * a port that only read would leave whoever built the acting half free to reach
 * a client of their own, which is what going through the SDK exists to prevent.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives: each stack's session is separate and
 * every connection is pinned against that stack's fingerprint, so a port
 * taking a client would let a caller pair the two up wrongly.
 *
 * **Acting takes an {@see AgreedTo}, which rendering cannot produce.** That is
 * {@see Confirmed}'s argument applied to a verb: a disruptive
 * action to state what it disturbs before it is confirmed, and the way that
 * requirement is broken is never deliberate — a screen draws a row, the stop
 * button is right there, and a tap handler calls the thing that stops it.
 * Nothing in the code says *this was confirmed*, because nothing had to.
 *
 * **There is no `everything()`.** A caller that wants the whole stack stopped
 * says so form by form, which is the granularity named. A single verb
 * for the machine would be one tap between an operator and a house with nothing
 * working, and the confirmation would be asked about a list nobody could read
 * in one screen.
 */
interface Supervising
{
    /**
     * Read what the stack is running, or come away with a reason.
     *
     * Answers {@see WhatIsRunning} rather than raising, which `C1` requires: a
     * stack that is asleep, one on another network and one whose session has
     * ended are ordinary states of the world, and an operator is
     * told which of them they met.
     */
    public function running(Stack $stack, Session $session): WhatIsRunning;

    /**
     * Do what the operator agreed to, or come away with a reason.
     *
     * Answers {@see Underway} — the same value a repair answers with — because
     * the two are the same shape of act: the stack takes the work on and hands
     * back a name to ask about, or says why it will not. Reusing it is what
     * keeps one screen's *it is running* from meaning something different from
     * another's.
     */
    public function told(Stack $stack, Session $session, AgreedTo $agreed): Underway;
}
