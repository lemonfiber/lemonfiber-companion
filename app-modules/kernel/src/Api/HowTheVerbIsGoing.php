<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of a start, a stop or a restart the operator asked for.
 *
 * A verb answers a handle, and this is the reading of it. Shaped as
 * {@see HowTheCopyIsGoing} is, and its third arm means the same: a job the
 * stack no longer has an outcome for leaves the operator not knowing what the
 * verb came to, which is said as that rather than as a failure.
 */
final readonly class HowTheVerbIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?WhatTheVerbCameTo $done = null,
        private ?Obstacle $met = null,
        private bool $running = false,
    ) {}

    /** The stack is still carrying it out. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is the stack's report of it. */
    public static function done(WhatTheVerbCameTo $report): self
    {
        return new self(done: $report);
    }

    /** The stack has no outcome for it any more. */
    public static function ended(): self
    {
        return new self();
    }

    /** The stack could not be asked, and this is what was met. */
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
     * @param Closure(): T                  $stillRunning
     * @param Closure(WhatTheVerbCameTo): T $done
     * @param Closure(): T                  $ended
     * @param Closure(Obstacle): T          $met
     *
     * @return T
     */
    public function either(Closure $stillRunning, Closure $done, Closure $ended, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->done instanceof WhatTheVerbCameTo => $done($this->done),
            default => $ended(),
        };
    }
}
