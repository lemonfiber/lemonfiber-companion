<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** The lines the jobs a screen follows say on a stack's event stream, as they arrive, heard in one reading. */
interface HearingTheJobs
{
    public function whatTheySaid(Stack $stack, Session $session, Job $job, Job ...$others): WhatTheJobsSaid;

    /** Let go of the stream, forgetting where it left off. */
    public function letGo(): WhatTheJobsSaid;

    /** Let go of the stream, keeping where it left off, so the next reading hears what the stack still holds of what was said since. */
    public function letGoKeepingThePlace(): WhatTheJobsSaid;
}
