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
     * @param HowTheReadingWent            $went      whether the rehearsal came back, and what stood in the way where it did not
     * @param bool                         $namesWhat whether the screen was opened on pausing or on resuming at all
     * @param bool                         $isWorking whether the stack is still reading the clients
     * @param bool                         $hasEnded  whether the stack has no outcome for the rehearsal any more
     * @param list<ADownloadClientAsShown> $clients   every client and what it is doing now, in the stack's order
     * @param string                       $caution   what a resume runs into, in the stack's words, or empty
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $namesWhat,
        public bool $isWorking,
        public bool $hasEnded,
        public array $clients,
        public string $caution,
    ) {}
}
