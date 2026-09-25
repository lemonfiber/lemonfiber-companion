<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What starting a form would come to, flattened for the template.
 *
 * A rehearsal: the template draws it under a heading that says so, and never
 * as services that have started.
 */
final readonly class WhatStartingItWouldShow
{
    /**
     * @param HowTheReadingWent            $went       whether the rehearsal came back, and what stood in the way where it did not
     * @param list<string>                 $wouldStart the services starting it would bring up, in the stack's order
     * @param list<AProfileLeftOutAsShown> $leftOut    the profiles it would leave out, each with what it would need
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $wouldStart,
        public array $leftOut,
    ) {}
}
