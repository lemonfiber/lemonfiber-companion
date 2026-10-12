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
    /** Ask what the change would do, writing nothing to any client. */
    public function rehearse(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheLineChange;

    /** Make the change, as the rehearsal on the screen described it. */
    public function carryOut(Stack $stack, Session $session, ALineChange $change): WhatBecameOfTheLineChange;

    /** What became of either, by the handle it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheLineChange;
}
