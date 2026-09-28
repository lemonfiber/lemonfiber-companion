<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what taking lemonfiber off its machine would come to, doing it, and what became of it.
 *
 * One port for the three, for {@see KeepingCurrent}'s reason: they are one
 * conversation, and a port that only read would leave whoever built the yes
 * free to reach a client of their own.
 *
 * **The reading is answered at once; the removal is work to follow.** Reading
 * changes nothing and answers {@see WhatWasFoundOfTheUninstall}; the removal
 * answers {@see WhatBecameOfTheUninstall}, whose first arm is the handle
 * {@see self::whatBecameOf()} follows.
 *
 * **Taking it off takes an {@see AnUninstallAgreed}**, which only a reading can
 * produce, so what is removed is what was listed.
 */
interface TakingLemonfiberOff
{
    /** Read what that removal would come to, removing nothing, or come away with a reason. */
    public function surveyed(Stack $stack, Session $session, WhichRemoval $tier): WhatWasFoundOfTheUninstall;

    /** Take it off, as the reading the operator was shown listed. */
    public function takeItOff(Stack $stack, Session $session, AnUninstallAgreed $agreed): WhatBecameOfTheUninstall;

    /** What became of the removal, by the handle it answered with. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheUninstall;
}
