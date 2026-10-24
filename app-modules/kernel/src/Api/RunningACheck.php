<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Running checks that disturb something, and following the run.
 *
 * A run is a diagnosis with the checks that disturb included: narrowed to the
 * check or group the stack named for one finding, to one family, or to none.
 * It is never rehearsed, since the check is the disturbance, and it answers
 * with the report the run came to.
 */
interface RunningACheck
{
    /**
     * Run this check.
     *
     * @return WhatBecameOfTheWork<Report>
     */
    public function run(Stack $stack, Session $session, ACheckThatDisturbs $check): WhatBecameOfTheWork;

    /**
     * Run every check this covers, the ones that disturb included.
     *
     * @return WhatBecameOfTheWork<Report>
     */
    public function runEvery(Stack $stack, Session $session, WhatARunCovers $covers): WhatBecameOfTheWork;

    /**
     * What became of it, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<Report>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
