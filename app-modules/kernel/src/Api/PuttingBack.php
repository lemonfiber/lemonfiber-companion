<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what putting a copy back would do, telling it to, and
 * asking what became of it.
 *
 * One conversation, so one port, for the reason {@see KeepingCurrent} gives.
 *
 * **Putting a copy back takes the listing, which only a rehearsal produces.**
 * {@see WhatPuttingItBackWouldDo} is built from what the stack said it would
 * do, and the yes quotes the listing's name, so a restore nobody was shown
 * cannot reach this port.
 */
interface PuttingBack
{
    /**
     * What putting that copy back would do, changing nothing, or a reason.
     *
     * Answers {@see WhatTheRestoreRehearsalFound} rather than raising.
     */
    public function rehearse(Stack $stack, Session $session, ACopy $copy): WhatTheRestoreRehearsalFound;

    /**
     * Put the copy back as listed, or come away with a reason.
     *
     * Answers {@see Underway}: the stack takes the work on and hands back
     * something to follow it by.
     */
    public function putBack(Stack $stack, Session $session, WhatPuttingItBackWouldDo $listed): Underway;

    /** What became of putting it back, by the handle agreeing answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowPuttingItBackIsGoing;
}
