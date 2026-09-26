<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of a support bundle the operator asked for.
 *
 * Shaped as {@see HowTheCopyIsGoing} is, with one arm more: the stack can
 * refuse a bundle it will not write, and says why in its own words. That is
 * an answer about the bundle rather than a stack that could not be reached,
 * so it is carried as one and never as an obstacle to try again.
 */
final readonly class HowTheBundleIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?ABundle $done = null,
        private ?string $refused = null,
        private ?Obstacle $met = null,
        private bool $running = false,
    ) {}

    /** The stack is still gathering it. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is the bundle the stack described or wrote. */
    public static function done(ABundle $bundle): self
    {
        return new self(done: $bundle);
    }

    /** The stack refused it, and said why. */
    public static function refused(string $said): self
    {
        return new self(refused: $said);
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
     * @param Closure(): T         $stillRunning
     * @param Closure(ABundle): T  $done
     * @param Closure(string): T   $refused
     * @param Closure(): T         $ended
     * @param Closure(Obstacle): T $met
     *
     * @return T
     */
    public function either(Closure $stillRunning, Closure $done, Closure $refused, Closure $ended, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running => $stillRunning(),
            $this->done instanceof ABundle => $done($this->done),
            $this->refused !== null => $refused($this->refused),
            default => $ended(),
        };
    }
}
