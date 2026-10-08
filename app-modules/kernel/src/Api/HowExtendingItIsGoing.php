<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of a plugin rehearsal or install the operator asked for.
 *
 * Answered both by asking and by following: the stack takes the work on and
 * names it, and the name is what it is followed by until it finishes.
 *
 * Shaped as {@see HowPuttingARunBackIsGoing} is. A job the stack no longer has
 * an outcome for leaves the operator not knowing what happened, which is said
 * as that rather than as a failure.
 *
 * **A refusal is the stack's answer, in its own words.** A source holding no
 * plugin, a manifest this build refuses, a value left unapproved, a recipe
 * that did not hold and an offer that moved are each turned down with a
 * sentence, and a screen draws it as that reason rather than as something to
 * try again.
 */
final readonly class HowExtendingItIsGoing
{
    /** Every field defaults, and each constructor says only its own state. */
    private function __construct(
        private ?ThePlugins $done = null,
        private ?ARefusalInItsWords $refused = null,
        private ?Obstacle $met = null,
        private ?Job $running = null,
    ) {}

    /** The stack took it on, or is still at it, and this is what to ask after it by. */
    public static function underway(Job $job): self
    {
        return new self(running: $job);
    }

    /** It finished, and this is what the stack said of its plugins. */
    public static function done(ThePlugins $plugins): self
    {
        return new self(done: $plugins);
    }

    /** The stack refused it, and this is why. */
    public static function refused(ARefusalInItsWords $why): self
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
     * A refusal of the asking itself, before any work was named, arrives here
     * as one of the work, in the stack's words.
     *
     * @template T of object
     *
     * @param Closure(Job): T                $underway
     * @param Closure(ThePlugins): T         $done
     * @param Closure(ARefusalInItsWords): T $refused
     * @param Closure(): T                   $ended
     * @param Closure(Obstacle): T           $met
     *
     * @return T
     */
    public function either(Closure $underway, Closure $done, Closure $refused, Closure $ended, Closure $met): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->running instanceof Job => $underway($this->running),
            $this->done instanceof ThePlugins => $done($this->done),
            $this->refused instanceof ARefusalInItsWords => $refused($this->refused),
            default => $ended(),
        };
    }
}
