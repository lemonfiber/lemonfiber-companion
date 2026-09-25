<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What starting a form would come to, as the stack rehearsed it.
 *
 * A rehearsal and nothing more: nothing was started by asking. The services
 * are the ones starting would bring up, and the profiles left out are the ones
 * the stack would filter out of the form, each with what it would have needed.
 * Both are the stack's answer; this app holds no copy of what a form is.
 */
final readonly class WhatStartingItWouldComeTo
{
    private function __construct(
        private Services $wouldStart,
        private TheProfilesLeftOut $leftOut,
    ) {}

    public static function rehearsed(Services $wouldStart, TheProfilesLeftOut $leftOut): self
    {
        return new self($wouldStart, $leftOut);
    }

    /** The services starting it would bring up. */
    public function wouldStart(): Services
    {
        return $this->wouldStart;
    }

    /** The profiles it would leave out, each with what it would have needed. */
    public function leftOut(): TheProfilesLeftOut
    {
        return $this->leftOut;
    }
}
