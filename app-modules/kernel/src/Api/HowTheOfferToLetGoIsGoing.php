<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of asking a stack what stopping seeding one download would cost.
 *
 * The stack answers that question as a job, so this is the reading of a
 * handle, shaped as {@see HowTheOfferIsGoing} is and for its reasons: still
 * working it out, the offer, a job the stack no longer has an outcome for, and
 * a stack that could not be reached to ask.
 */
final readonly class HowTheOfferToLetGoIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?WhatLettingItGoCosts $offered = null,
        private ?Obstacle $met = null,
        private bool $running = false,
    ) {}

    /** The stack is still working out what it would cost. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is its offer. */
    public static function offering(WhatLettingItGoCosts $offer): self
    {
        return new self(offered: $offer);
    }

    /** The stack has no outcome for that job any more, and the remedy is to ask again. */
    public static function ended(): self
    {
        return new self();
    }

    /** The stack could not be reached to ask, and this is what was met. */
    public static function met(Obstacle $why): self
    {
        return new self(met: $why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Being unable to reach the stack outranks anything believed about the job.
     *
     * @template T of object
     *
     * @param Closure(): T                     $stillRunning
     * @param Closure(WhatLettingItGoCosts): T $offering
     * @param Closure(): T                     $ended
     * @param Closure(Obstacle): T             $met
     *
     * @return T
     */
    public function either(Closure $stillRunning, Closure $offering, Closure $ended, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->offered instanceof WhatLettingItGoCosts => $offering($this->offered),
            default => $ended(),
        };
    }
}
