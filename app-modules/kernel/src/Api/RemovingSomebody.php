<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack to take somebody out of the household: what it would cost, doing it, and what became of it.
 *
 * One port for the three, for {@see KeepingCurrent}'s reason: they are one
 * conversation, and a port that only described the cost would leave whoever
 * built the yes free to reach a client of their own.
 *
 * **Every act is work the stack names and this follows.** The stack answers
 * each with a handle, and the removal arrives through
 * {@see self::whatBecameOf()} once the work is done, so each answers
 * {@see WhatBecameOfTheRemoval}, whose first arm is the handle.
 *
 * **Taking them out takes an {@see ARemovalAgreed}**, which only a reading
 * nobody agreed to can produce, so the person taken out is the person whose
 * cost was shown.
 */
interface RemovingSomebody
{
    /** Ask what taking them out would cost, taking nobody out. */
    public function wouldRemove(Stack $stack, Session $session, SomebodyInTheHousehold $who): WhatBecameOfTheRemoval;

    /** Take them out, as the reading the operator was shown described. */
    public function remove(Stack $stack, Session $session, ARemovalAgreed $agreed): WhatBecameOfTheRemoval;

    /** What became of work one of the two started, by the handle it answered with. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheRemoval;
}
