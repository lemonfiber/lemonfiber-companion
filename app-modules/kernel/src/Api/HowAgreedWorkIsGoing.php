<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What became of work the operator agreed to: a repair, an update, or a start, stop or restart.
 *
 * Agreeing answers a handle, and this is the reading of it. One type for all
 * three, because each reads the same five ways; what differs is only what the
 * finished arm carries, and that is the type parameter: a port answers
 * `HowAgreedWorkIsGoing<WhatWasMended>`, `<Upkeep>` or `<WhatTheVerbCameTo>`,
 * so the done arm stays typed and a screen cannot read one report as another.
 *
 * **The third arm is not a failure.** A job the stack has no outcome for any
 * more leaves the operator not knowing what happened to their machine, and
 * the app says exactly that rather than *it failed*, because it may well have
 * worked. The remedy is to look at the stack again, not to agree a second time.
 *
 * **The fifth arm is an answer.** The stack refused the yes because what it was
 * given for has moved since the operator read it: nothing was carried out, the
 * stack's words say what moved, and what is owed next is what it would do
 * now, offered again.
 *
 * Every field defaults, and each constructor says only its own state: a value
 * {@see self::either()} never reads is one no test can hold to being right.
 *
 * @template-covariant TDone of object = never
 */
final readonly class HowAgreedWorkIsGoing
{
    /** @param TDone|null $done */
    private function __construct(
        private ?object $done = null,
        private ?Obstacle $met = null,
        private bool $running = false,
        private ?ARefusalInItsWords $moved = null,
    ) {}

    /**
     * The stack is still carrying it out.
     *
     * @return self<never>
     */
    public static function stillRunning(): self
    {
        return new self(running: true);
    }

    /**
     * It finished, and this is the stack's report of it.
     *
     * @template TReport of object
     *
     * @param TReport $report
     *
     * @return self<TReport>
     */
    public static function done(object $report): self
    {
        return new self(done: $report);
    }

    /**
     * The stack has no outcome for it any more.
     *
     * @return self<never>
     */
    public static function ended(): self
    {
        return new self();
    }

    /**
     * The stack could not be asked, and this is what was met.
     *
     * @return self<never>
     */
    public static function met(Obstacle $why): self
    {
        return new self(met: $why);
    }

    /**
     * The stack refused the yes because what it was given for has moved.
     *
     * @return self<never>
     */
    public static function moved(ARefusalInItsWords $why): self
    {
        return new self(moved: $why);
    }

    /**
     * Say what happens in each of the five, and get back what you built.
     *
     * Being unable to reach the stack outranks anything believed about the
     * job, because what is believed was read from a machine that is not
     * answering now.
     *
     * @template TRunning of object
     * @template TFinished of object
     * @template TEnded of object
     * @template TMet of object
     * @template TMoved of object
     *
     * @param Closure(): TRunning                 $stillRunning
     * @param Closure(TDone): TFinished           $done
     * @param Closure(): TEnded                   $ended
     * @param Closure(Obstacle): TMet             $met
     * @param Closure(ARefusalInItsWords): TMoved $moved
     *
     * @return TRunning|TFinished|TEnded|TMet|TMoved
     */
    public function either(Closure $stillRunning, Closure $done, Closure $ended, Closure $met, Closure $moved): object
    {
        return match (true) {
            $this->met instanceof Obstacle => $met($this->met),
            $this->moved instanceof ARefusalInItsWords => $moved($this->moved),
            $this->running => $stillRunning(),
            $this->done !== null => $done($this->done),
            default => $ended(),
        };
    }
}
