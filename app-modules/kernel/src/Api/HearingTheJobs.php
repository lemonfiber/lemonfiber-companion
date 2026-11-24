<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** The lines the jobs a screen follows say on a stack's event stream, as they arrive, heard in one reading. */
interface HearingTheJobs
{
    public function whatTheySaid(Stack $stack, Session $session, Job $job, Job ...$others): WhatTheJobsSaid;

    public function letGo(): WhatTheJobsSaid;
}
