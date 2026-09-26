<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a machine what it keeps running when nobody is signed in.
 *
 * The one thing on this subject an operator cannot find out from a phone any
 * other way. Everything else the app shows about a stack is true while somebody
 * is looking at it; this is the question about the hours nobody was.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives: each stack's session is separate and every connection is
 * pinned against that stack's fingerprint, so a port taking a client would let
 * a caller pair the two up wrongly. This signature makes that mistake
 * unspellable.
 *
 * **Reading and handing over are one port**, for {@see Supervising}'s reason:
 * an operator reads the listing, picks a command, and asks for it to be kept
 * running or taken back — and a port that only read would leave whoever built
 * the acting half free to reach a client of their own.
 *
 * **Handing over takes a {@see HostingAgreed}, which rendering cannot
 * produce.** Installing is an act of its own and never the side effect of
 * another, so the only way to one is a value built from what the operator
 * agreed to.
 */
interface Hosting
{
    /**
     * Ask a machine what it keeps running, or come away with a reason.
     *
     * Answers {@see WhatKeepsRunning} rather than raising, which `C1` requires:
     * a stack that is asleep, one on another network and one whose session has
     * ended are ordinary states of the world, and an operator is told which of
     * them they met.
     *
     * A machine with no service manager answers through the *first* arm, not
     * this one. It is a fact about the platform rather than a failure to reach
     * it, and an operator told to check their network about a laptop that was
     * never going to run a launch agent has been sent to fix the wrong thing.
     */
    public function keptRunningOn(Stack $stack, Session $session): WhatKeepsRunning;

    /**
     * Hand one command to the machine or take it back, or come away with a reason.
     *
     * Answers {@see HowTheHandoverWent} rather than raising, for the reason
     * {@see keptRunningOn()} does; and a stack that answered and refused is
     * told apart from one that never answered, because only the first has
     * said why.
     */
    public function handOver(Stack $stack, Session $session, HostingAgreed $agreed): HowTheHandoverWent;
}
