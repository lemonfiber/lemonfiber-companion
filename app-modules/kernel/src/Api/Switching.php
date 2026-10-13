<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Making a chosen set of forms the stack's active set: rehearsed first, carried out on a yes, and followed.
 */
interface Switching
{
    /**
     * Ask what the switch would stop, start and keep, moving nothing.
     *
     * @return WhatBecameOfTheWork<TheSwitch>
     */
    public function rehearse(Stack $stack, Session $session, TheFormsToSwitchTo $forms): WhatBecameOfTheWork;

    /**
     * Make the switch, as the rehearsal on the screen described it.
     *
     * @return WhatBecameOfTheWork<TheSwitch>
     */
    public function carryOut(Stack $stack, Session $session, TheFormsToSwitchTo $forms): WhatBecameOfTheWork;

    /**
     * What became of either, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<TheSwitch>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
