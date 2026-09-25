<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What starting a form would come to, as the stack rehearsed it.
 *
 * A rehearsal and nothing more: nothing was started by asking. The services
 * are the ones starting would bring up, the services left out are the ones
 * the stack would filter out of the form, each with what it would have
 * needed, and the footprint is the stack's estimate of what starting would
 * take. All of it is the stack's answer; this app holds no copy of what a
 * form is.
 */
final readonly class WhatStartingItWouldComeTo
{
    private function __construct(
        private Services $wouldStart,
        private TheServicesLeftOut $leftOut,
        private AFootprint $footprint,
    ) {}

    public static function rehearsed(Services $wouldStart, TheServicesLeftOut $leftOut, AFootprint $footprint): self
    {
        return new self($wouldStart, $leftOut, $footprint);
    }

    /** The services starting it would bring up. */
    public function wouldStart(): Services
    {
        return $this->wouldStart;
    }

    /** The services it would leave out, each with what it would have needed. */
    public function leftOut(): TheServicesLeftOut
    {
        return $this->leftOut;
    }

    /** What the stack estimates starting it would take. */
    public function footprint(): AFootprint
    {
        return $this->footprint;
    }
}
