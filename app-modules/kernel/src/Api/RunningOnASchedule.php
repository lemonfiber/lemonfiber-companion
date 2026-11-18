<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack about its run-and-exit services, and running one at once.
 */
interface RunningOnASchedule
{
    /** Ask a stack how each of its run-and-exit services last ran, or come away with a reason. */
    public function scheduled(Stack $stack, Session $session): WhatRunsOnASchedule;

    /**
     * Run one at once, whether or not one of its times fell due.
     *
     * @return WhatBecameOfTheWork<WhatRunningItNowCameTo>
     */
    public function runNow(Stack $stack, Session $session, ServiceId $service): WhatBecameOfTheWork;

    /**
     * What became of that run, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<WhatRunningItNowCameTo>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
