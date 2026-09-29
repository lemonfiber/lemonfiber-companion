<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack to guard its data location for some of its forms, following
 * the guard, and letting it go.
 *
 * One port for the reason {@see KeepingCurrent} gives: starting, following and
 * releasing are one conversation, and a port that only started would leave
 * whoever built the rest free to reach a client of their own.
 *
 * **A guard started here is held by asking.** It has no ending of its own, so
 * the stack keeps it only while somebody keeps asking what became of it, and
 * each {@see whatBecameOf()} is what keeps it. {@see letGo()} ends it.
 */
interface Guarding
{
    /**
     * Start the guard asked for, or come away with a reason.
     *
     * Answers {@see Underway}: the stack takes the guard on and hands back
     * the name to ask after it by.
     */
    public function guard(Stack $stack, Session $session, AGuardAskedFor $asked): Underway;

    /** Where the guard stands, by the name starting it answered. Asking is what keeps it. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheGuardIsGoing;

    /** End the guard, and say where it stood when it ended. */
    public function letGo(Stack $stack, Session $session, Job $job): HowTheGuardIsGoing;
}
