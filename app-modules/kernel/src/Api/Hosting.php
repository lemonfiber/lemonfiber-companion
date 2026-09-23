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
 * **It reads and does not act.** There is no install and no take-it-back here,
 * and that is the requirement rather than an omission: what is configured is
 * the core's, and a phone that could turn *come back after a restart* on and
 * off would be a second place the answer is decided. The port has one method
 * because it has one question.
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
}
