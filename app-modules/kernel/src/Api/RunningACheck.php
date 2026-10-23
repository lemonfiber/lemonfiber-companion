<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Running a check the stack leaves unverified because it disturbs something, and following the run.
 *
 * The run is a diagnosis narrowed to the check or group the stack named for it,
 * with the checks that disturb included, and it is never rehearsed: the check
 * is the disturbance. It answers with the report the run came to.
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
     * What became of it, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<Report>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
