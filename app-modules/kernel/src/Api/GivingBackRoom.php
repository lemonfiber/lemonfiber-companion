<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Giving back one category of the room a stack offers as costing nothing, on a yes, and following it.
 *
 * The offer itself is read by {@see Measuring}.
 */
interface GivingBackRoom
{
    /**
     * Give back the category agreed to.
     *
     * @return WhatBecameOfTheWork<TheRoomGivenBack>
     */
    public function giveBack(Stack $stack, Session $session, ARoomGivenBackAgreed $agreed): WhatBecameOfTheWork;

    /**
     * What became of it, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<TheRoomGivenBack>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
