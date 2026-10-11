<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of rehearsing or carrying out a pause or a resume, by the handle it answered.
 *
 * Shaped as {@see HowLettingItGoIsGoing} is, and one type for both halves,
 * because the stack answers both with the same report: the rehearsal's is the
 * offer, the yes's is what each client read back. A job the stack no longer
 * has an outcome for is said as that rather than as a failure.
 */
final readonly class HowPausingIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?WhatPausingComesTo $answered = null,
        private ?Obstacle $met = null,
        private bool $running = false,
    ) {}

    /** The stack is still asking the clients. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is the stack's report of it. */
    public static function answered(WhatPausingComesTo $report): self
    {
        return new self(answered: $report);
    }

    /** The stack has no outcome for it any more. */
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
     * @param Closure(): T                   $stillRunning
     * @param Closure(WhatPausingComesTo): T $answered
     * @param Closure(): T                   $ended
     * @param Closure(Obstacle): T           $met
     *
     * @return T
     */
    public function either(Closure $stillRunning, Closure $answered, Closure $ended, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->answered instanceof WhatPausingComesTo => $answered($this->answered),
            default => $ended(),
        };
    }
}
