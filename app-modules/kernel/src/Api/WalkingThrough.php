<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack to walk through fetching one thing, and following it to its record.
 *
 * Starting and following are one port, for the reason {@see KeepingCurrent}
 * gives: they are one conversation, and a port that only followed would leave
 * whoever built the starting half free to reach a client of their own.
 *
 * **The narration arrives once, whole.** The stack also narrates each step as
 * it happens on its event stream, and this app does not read that stream: the
 * handle is asked after at a stated cadence while the walk runs, and the lines
 * are drawn from the record the finished walk answers with.
 */
interface WalkingThrough
{
    /**
     * Start a walkthrough of what was asked for, or come away with a reason.
     *
     * Answers {@see Underway}, because the stack takes the work on and hands
     * back something to follow it by.
     */
    public function walk(Stack $stack, Session $session, WhatToWalk $asked): Underway;

    /** What became of a walkthrough started, by the handle starting it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowTheWalkthroughIsGoing;
}
