<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What pausing or resuming the downloads would do, flattened for a template.
 *
 * Its own model rather than shared with {@see HowPausingWent}, so a rehearsal
 * and a report of what happened are drawn from different values and one can
 * never be drawn as the other.
 */
final readonly class WhatPausingWouldShow
{
    /**
     * @param WhereTheWorkStands            $stands      where the work stands, and whether the stack could be asked at all
     * @param bool                         $namesWhat whether the screen was opened on pausing or on resuming at all
     * @param ?WhatTheClientsSaid          $said      what every client is doing now, where the stack rehearsed it
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public bool $namesWhat = true,
        public ?WhatTheClientsSaid $said = null,
    ) {}
}
