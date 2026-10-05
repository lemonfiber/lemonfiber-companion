<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what is already on its machine, and moving in beside it.
 *
 * The survey is a read: it chooses no mode and moves nothing. Moving in is an
 * act per mode, and each is asked twice — without the yes it says what it
 * would come to and does nothing, and with it it is carried out. It takes a
 * stack and a session rather than a client, for the reason {@see Asking} gives.
 *
 * **Reading and acting are one port**, for {@see Hosting}'s reason: an
 * operator reads the survey, picks a mode from it and agrees to it, and a port
 * that only read would leave whoever built the acting half free to reach a
 * client of their own.
 *
 * **The yes takes an {@see AMoveAgreed}**, which only a pending answer to the
 * same act asked without it can build. So the move carried out is the one the
 * operator was shown, copy wanted first and all.
 *
 * **Every act is work the stack names and this follows.** The stack answers
 * each with a handle, and where the move stands arrives through
 * {@see self::whatBecameOf()} once the work is done.
 */
interface MovingIn
{
    /** Ask a stack what it found already standing, or come away with a reason. */
    public function surveyedOn(Stack $stack, Session $session): WhatWasFoundAlreadyHere;

    /** Ask what moving in this way would come to, doing nothing. */
    public function wouldMoveIn(Stack $stack, Session $session, MovingInBy $by): WhatBecameOfTheMove;

    /** Move in the way the operator agreed to, having been shown what it would come to. */
    public function moveIn(Stack $stack, Session $session, AMoveAgreed $agreed): WhatBecameOfTheMove;

    /** What became of work either act started, by the handle it answered with. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheMove;
}
