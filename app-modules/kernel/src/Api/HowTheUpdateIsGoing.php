<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of an update the operator took.
 *
 * Taking an update answers a handle, and this is the reading of it. The
 * finished arm carries the update's own report, which is the only place the
 * stack says how each service took it: a plain reading of where the stack
 * stands leaves that out, so a screen that drew it off the reading would
 * always say that nothing has been taken.
 *
 * Shaped as {@see HowTheRepairIsGoing} is, and its third arm means the same:
 * a job that ended with no outcome leaves the operator not knowing what
 * happened to their services, and the app says exactly that rather than *it
 * failed*, because it may well have worked.
 */
final readonly class HowTheUpdateIsGoing
{
    /** Every field defaults, and each constructor says only its own state, for {@see HowTheRepairIsGoing}'s reason. */
    private function __construct(
        private ?Upkeep $done = null,
        private ?Obstacle $met = null,
        private bool $running = false,
        private ?ARefusalInItsWords $moved = null,
    ) {}

    /** The stack is still carrying the update out. */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /** It finished, and this is the stack's report of it. */
    public static function done(Upkeep $report): self
    {
        return new self(done: $report);
    }

    /** The stack has no outcome for that update any more. */
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
     * The stack refused the yes because what it was given for has moved.
     *
     * An answer rather than a failure, as a repair's is
     * ({@see HowTheRepairIsGoing::moved()}): nothing was carried out, and the
     * update agreed to no longer stands. The stack's words say what moved, and
     * what is owed next is what it would do now, offered again.
     */
    public static function moved(ARefusalInItsWords $why): self
    {
        return new self(moved: $why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * The order is {@see HowTheRepairIsGoing::either()}'s: being unable to
     * reach the stack outranks anything believed about the job.
     *
     * @template T of object
     *
     * @param Closure(): T       $stillRunning
     * @param Closure(Upkeep): T $done
     * @param Closure(): T       $ended
     * @param Closure(Obstacle): T $met
     * @param Closure(ARefusalInItsWords): T $moved
     *
     * @return T
     */
    public function either(Closure $stillRunning, Closure $done, Closure $ended, Closure $met, Closure $moved): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->moved instanceof ARefusalInItsWords => $moved($this->moved),
            $this->running => $stillRunning(),
            $this->done instanceof Upkeep => $done($this->done),
            default => $ended(),
        };
    }
}
