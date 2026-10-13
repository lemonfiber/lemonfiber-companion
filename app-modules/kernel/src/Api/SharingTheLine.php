<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Changing how a stack shares its line: rehearsed first, carried out on a yes, and followed.
 *
 * The stack answers both halves as work it names, so each answers the handle
 * and {@see self::whatBecameOf()} reads what the work came to.
 */
interface SharingTheLine
{
    /**
     * Ask what the change would do, writing nothing to any client.
     *
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    public function rehearse(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheWork;

    /**
     * Make the change, as the rehearsal on the screen described it.
     *
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    public function carryOut(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheWork;

    /**
     * What became of either, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<TheLineAsChanged>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
