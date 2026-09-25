<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of a walkthrough the operator started.
 *
 * Starting one answers a handle, and this is the reading of it. The finished
 * arm carries the walkthrough's own report, which is the only place its lines
 * arrive: while it runs, the handle says only that it is running.
 *
 * Shaped as {@see HowTheUpdateIsGoing} is, and its third arm means the same: a
 * job the stack no longer knows ended with no outcome to give, which is not
 * the same as failing — it may well have worked.
 */
final readonly class HowTheWalkthroughIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?AWalkthrough $done = null,
        private ?Obstacle $met = null,
        private bool $running = false,
    ) {}

    /** The stack is still walking it. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is the stack's report of it. */
    public static function done(AWalkthrough $report): self
    {
        return new self(done: $report);
    }

    /** The stack has no outcome for that walkthrough any more. */
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
     * @param Closure(): T             $stillRunning
     * @param Closure(AWalkthrough): T $done
     * @param Closure(): T             $ended
     * @param Closure(Obstacle): T     $met
     *
     * @return T
     */
    public function either(Closure $stillRunning, Closure $done, Closure $ended, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->done instanceof AWalkthrough => $done($this->done),
            default => $ended(),
        };
    }
}
