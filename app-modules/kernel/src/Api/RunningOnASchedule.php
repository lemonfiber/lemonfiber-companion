<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack about its run-and-exit services.
 */
interface RunningOnASchedule
{
    /** Ask a stack how each of its run-and-exit services last ran, or come away with a reason. */
    public function scheduled(Stack $stack, Session $session): WhatRunsOnASchedule;
}
