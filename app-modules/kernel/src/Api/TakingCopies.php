<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Telling a stack to take a copy of itself, and asking what became of it.
 *
 * Acting and following are one port for the reason {@see KeepingCurrent}
 * gives. It takes a stack and a session rather than a client, so the session
 * and the pinning cannot be paired up wrongly by a caller.
 */
interface TakingCopies
{
    /**
     * Take the copy asked for, or come away with a reason.
     *
     * Answers {@see Underway}: the stack takes the work on and hands back
     * something to follow it by.
     */
    public function take(Stack $stack, Session $session, ACopyAsked $asked): Underway;

    /** What became of a copy asked for, by the handle asking for it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheCopyIsGoing;
}
