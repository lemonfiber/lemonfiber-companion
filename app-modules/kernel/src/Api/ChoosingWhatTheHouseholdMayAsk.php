<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Choosing what the household, or one member, may ask for: rehearsed first, carried out on a yes, and followed.
 */
interface ChoosingWhatTheHouseholdMayAsk
{
    /** Ask what the change would do, writing nothing. */
    public function rehearse(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheAllowanceChange;

    /** Make the change, as the rehearsal on the screen described it. */
    public function carryOut(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheAllowanceChange;

    /** What became of either, by the handle it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheAllowanceChange;
}
