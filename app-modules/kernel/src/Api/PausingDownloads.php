<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Rehearsing a pause or a resume of every download client, carrying it out, and asking what became of each.
 *
 * Four methods for {@see StoppingSeeding}'s reason: the stack answers the
 * rehearsal and the yes each as a job, and a port that waited for either would
 * be a screen that freezes on a machine that may be asleep.
 *
 * **Carrying it out takes the rehearsal's report, which only the stack
 * produces.** The yes quotes the offer that report names, so clients nobody
 * was shown cannot reach this port, and a yes against clients that have moved
 * since is refused by the stack.
 */
interface PausingDownloads
{
    /**
     * Ask what pausing or resuming would do, asking no client anything.
     *
     * Answers {@see Underway}: the stack names the work of reading every
     * client, and {@see whatBecameOf()} reads it.
     */
    public function rehearse(Stack $stack, Session $session, WhatToDoWithTheDownloads $asked): Underway;

    /**
     * Carry out what the rehearsal offered, or come away with a reason.
     *
     * Answers {@see Underway}: the stack takes the work on and hands back
     * something to follow it by.
     */
    public function carryOut(Stack $stack, Session $session, WhatPausingComesTo $offer): Underway;

    /** What became of either, by the handle it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPausingIsGoing;
}
