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
     * @param HowTheReadingWent       $went whether the rehearsal came back, and what stood in the way where it did not
     * @param TheRehearsalAsRead|null $read what it came to, where it came back
     */
    public function __construct(public HowTheReadingWent $went, public ?TheRehearsalAsRead $read = null) {}
}
