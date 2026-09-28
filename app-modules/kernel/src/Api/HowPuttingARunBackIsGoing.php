<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of putting a run back that the operator agreed to.
 *
 * Shaped as {@see HowPuttingItBackIsGoing} is. A job the stack no longer has
 * an outcome for leaves the operator not knowing what went back, which is said
 * as that rather than as a failure.
 *
 * The stack can refuse the run, and says why in its own words. That is an
 * answer about the run rather than a stack that could not be reached, so it is
 * carried as one and never as an obstacle to try again.
 */
final readonly class HowPuttingARunBackIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?ARunPutBack $done = null,
        private ?WhyItWasNotPutBack $refused = null,
        private ?Obstacle $met = null,
        private bool $running = false,
    ) {}

    /** The stack is still putting it back. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is the stack's report of it. */
    public static function done(ARunPutBack $report): self
    {
        return new self(done: $report);
    }

    /** The stack refused it, and this is why. */
    public static function refused(WhyItWasNotPutBack $why): self
    {
        return new self(refused: $why);
    }

    /** The stack has no outcome for it any more. */
    public static function ended(): self
    {
        return new self();
    }

    /** The stack could not be reached to ask, or refused the session, and this is what was met. */
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
     * @param Closure(ARunPutBack): T        $done
     * @param Closure(WhyItWasNotPutBack): T $refused
     * @param Closure(): T                   $ended
     * @param Closure(Obstacle): T           $met
     *
     * @return T
     */
    public function either(Closure $stillRunning, Closure $done, Closure $refused, Closure $ended, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->done instanceof ARunPutBack => $done($this->done),
            $this->refused instanceof WhyItWasNotPutBack => $refused($this->refused),
            default => $ended(),
        };
    }
}
