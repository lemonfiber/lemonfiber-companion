<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What starting a form would come to, flattened for the template.
 *
 * A rehearsal: the template draws it under a heading that says so, and never
 * as services that have started. The footprint is the stack's estimate and
 * is drawn as one.
 */
final readonly class WhatStartingItWouldShow
{
    /**
     * @param HowTheReadingWent            $went        whether the rehearsal came back, and what stood in the way where it did not
     * @param list<string>                 $wouldStart  the services starting it would bring up, in the stack's order
     * @param list<AServiceLeftOutAsShown> $leftOut     the services it would leave out, each with what it would need
     * @param int|null                     $estimatedMib what the stack estimates starting it would take, in mebibytes; null where nothing was read
     * @param list<string>                 $unestimated the services that would start with no estimate of their own
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $wouldStart,
        public array $leftOut,
        public ?int $estimatedMib,
        public array $unestimated,
    ) {}
}
