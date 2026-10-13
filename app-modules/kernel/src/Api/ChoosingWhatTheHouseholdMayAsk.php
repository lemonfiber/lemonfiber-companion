<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Choosing what the household, or one member, may ask for: rehearsed first, carried out on a yes, and followed.
 */
interface ChoosingWhatTheHouseholdMayAsk
{
    /**
     * Ask what the change would do, writing nothing.
     *
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheWork;

    /**
     * Make the change, as the rehearsal on the screen described it.
     *
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, AnAllowanceChange $change): WhatBecameOfTheWork;

    /**
     * What became of either, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<TheAllowanceAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
