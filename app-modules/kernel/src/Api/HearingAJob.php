<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** The lines one job says on a stack's event stream, as they arrive. */
interface HearingAJob
{
    public function whatItSaid(Stack $stack, Session $session, Job $job): WhatWasHeardFromAJob;

    public function letGo(): WhatWasHeardFromAJob;
}
