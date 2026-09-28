<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of stopping seeding one download, flattened for a template.
 *
 * Its own model rather than fields on {@see WhatLettingItGoWouldShow}, so an
 * offer of what it would cost and a report of what happened are drawn from
 * different values and one can never be drawn as the other.
 */
final readonly class HowLettingItGoWent
{
    /**
     * @param HowTheReadingWent $went         whether asking after it came back, and what stood in the way where it did not
     * @param bool              $isWorking    whether the stack is still asking the client
     * @param bool              $hasEnded     whether the stack has no outcome for it any more
     * @param bool              $wasRehearsed whether it was a rehearsal, which let nothing go and freed nothing
     * @param string            $name         what the client let go, or would have, or empty
     * @param ?ASizeAsShown     $size         what it occupied, or nothing where there is no report
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public bool $wasRehearsed,
        public string $name,
        public ?ASizeAsShown $size,
    ) {}
}
