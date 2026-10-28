<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of pausing or resuming the downloads, flattened for a template.
 *
 * Each client's line is drawn from what it read back after it was asked, never
 * from the rehearsal above it.
 */
final readonly class HowPausingWent
{
    /**
     * @param WhereTheWorkStands            $stands         where the work stands, and whether the stack could be asked at all
     * @param ?WhatTheClientsSaid          $said         every client, what it said before and what it read back after, where the stack reported
     * @param ?bool                        $wasRehearsed whether what it reported was a rehearsal, which asked no client anything
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?WhatTheClientsSaid $said = null,
        public ?bool $wasRehearsed = null,
    ) {}
}
