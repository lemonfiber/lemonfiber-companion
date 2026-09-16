<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack how it is.
 *
 * The first thing the application does with a session once it has one. Pairing
 * and signing in are both means to this end: an operator away from the machine
 * wants to know whether their stack is doing what it should, and until now the
 * app could get in and had nothing to show them.
 *
 * **It takes a stack and a session rather than a client.** `N1-R11` keeps each
 * stack's session separate and `N1-R19` pins every connection against the
 * fingerprint that stack's pairing material carried — a port taking a client
 * would let a caller pair the two up wrongly, which is the one mistake this
 * signature makes impossible. {@see Reaching} is what turns them into a
 * connection, behind the same module boundary.
 *
 * **One method, which is the whole of `N1-R65` on this side.** A screen showing
 * health asks once and renders what came back; it does not poll, and it does
 * not ask again to fill in a field it forgot. A port with a method per section
 * of the screen is a screen that opens four connections to a machine over a
 * home network.
 */
interface Asking
{
    /**
     * Ask a stack for its diagnostic report, or come away with a reason.
     *
     * Answers {@see WhatCameBack} rather than raising, which `C1` requires and
     * which is right for the same reason {@see Admitting} answers
     * {@see Admitted}: a stack that is asleep, one on another network and one
     * whose session has ended are ordinary states of the world rather than
     * faults, and `N1-R10` says an operator is told which.
     */
    public function about(Stack $stack, Session $session): WhatCameBack;
}
