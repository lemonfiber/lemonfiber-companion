<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a held subscription had to say when it was last asked.
 *
 * Five answers, because a screen does something different with each. Nothing
 * arrived, and the subscription is open. Something arrived that was not a
 * summary, which proves the stack is still there. A summary arrived. The
 * subscription is closed, by the stack or by the app. Or it could not be opened
 * or read, and the obstacle says why.
 *
 * **What the stack named as newest rides beside any of them, and so does what
 * answers what.** Each arrives on the same stream, when a listener arrives and
 * whenever it changes, so each is carried apart from the five rather than as a
 * sixth: a summary, the newest of each kind and the wiring can arrive in one
 * wake, and none of them is lost.
 */
final readonly class WhatWasHeard
{
    private function __construct(
        private WhatArrived $arrived,
        private ?TheHealthSummary $summary,
        private ?Obstacle $why,
        private ?TheNewestNamed $newest = null,
        private ?TheLinks $links = null,
    ) {}

    public static function nothing(): self
    {
        return new self(WhatArrived::Nothing, null, null);
    }

    public static function aSignOfLife(): self
    {
        return new self(WhatArrived::ASignOfLife, null, null);
    }

    public static function said(TheHealthSummary $summary): self
    {
        return new self(WhatArrived::ASummary, $summary, null);
    }

    public static function closed(): self
    {
        return new self(WhatArrived::TheEnd, null, null);
    }

    public static function met(Obstacle $why): self
    {
        return new self(WhatArrived::AnObstacle, null, $why);
    }

    /** The same, with the newest of each kind the stack named in this wake. */
    public function naming(TheNewestNamed $newest): self
    {
        return new self($this->arrived, $this->summary, $this->why, $newest, $this->links);
    }

    /** The same, with what answers what as the stack said it in this wake. */
    public function wiredAs(TheLinks $links): self
    {
        return new self($this->arrived, $this->summary, $this->why, $this->newest, $links);
    }

    /**
     * What answers what as the stack said it in this wake, where it said it.
     *
     * @template T of object
     *
     * @param Closure(TheLinks): T $said
     * @param Closure(): T $nothing
     *
     * @return T
     */
    public function whatAnswersWhat(Closure $said, Closure $nothing): object
    {
        return $this->links instanceof TheLinks ? $said($this->links) : $nothing();
    }

    /**
     * The newest of each kind the stack named in this wake, where it named them.
     *
     * @template T of object
     *
     * @param Closure(TheNewestNamed): T $named
     * @param Closure(): T $nothing
     *
     * @return T
     */
    public function theNewest(Closure $named, Closure $nothing): object
    {
        return $this->newest instanceof TheNewestNamed ? $named($this->newest) : $nothing();
    }

    /**
     * Every arm required, so a screen cannot forget that a subscription ends.
     *
     * @template T of object
     *
     * @param Closure(): T $nothing
     * @param Closure(): T $alive
     * @param Closure(TheHealthSummary): T $said
     * @param Closure(): T $closed
     * @param Closure(Obstacle): T $met
     *
     * @return T
     */
    public function either(Closure $nothing, Closure $alive, Closure $said, Closure $closed, Closure $met): object
    {
        return match (true) {
            $this->summary instanceof TheHealthSummary => $said($this->summary),
            $this->why instanceof Obstacle => $met($this->why),
            $this->arrived === WhatArrived::ASignOfLife => $alive(),
            $this->arrived === WhatArrived::TheEnd => $closed(),
            default => $nothing(),
        };
    }
}
