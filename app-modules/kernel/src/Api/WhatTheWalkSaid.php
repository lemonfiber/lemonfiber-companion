<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a held subscription had said about a running walkthrough when it was last asked.
 *
 * Five answers, for {@see WhatWasHeard}'s reason: a screen does something
 * different with each. Nothing arrived, and the subscription is open. Something
 * arrived that was not a step, which proves the stack is still there. A step
 * arrived, and it is the last one said since the screen last asked. The
 * subscription is closed, by the stack or by the app. Or it could not be opened
 * or read, and the obstacle says why.
 */
final readonly class WhatTheWalkSaid
{
    private function __construct(
        private ?ALineItSaid $line,
        private ?Obstacle $why,
        private bool $alive,
        private bool $closed,
    ) {}

    public static function nothing(): self
    {
        return new self(null, null, alive: false, closed: false);
    }

    public static function aSignOfLife(): self
    {
        return new self(null, null, alive: true, closed: false);
    }

    /** The last step the walk said, carried as the stack said it. */
    public static function said(ALineItSaid $line): self
    {
        return new self($line, null, alive: true, closed: false);
    }

    public static function closed(): self
    {
        return new self(null, null, alive: false, closed: true);
    }

    public static function met(Obstacle $why): self
    {
        return new self(null, $why, alive: false, closed: false);
    }

    /**
     * Every arm required, so a screen cannot forget that a subscription ends.
     *
     * @template T of object
     *
     * @param Closure(): T            $nothing
     * @param Closure(): T            $alive
     * @param Closure(ALineItSaid): T $said
     * @param Closure(): T            $closed
     * @param Closure(Obstacle): T    $met
     *
     * @return T
     */
    public function either(Closure $nothing, Closure $alive, Closure $said, Closure $closed, Closure $met): object
    {
        return match (true) {
            $this->line instanceof ALineItSaid => $said($this->line),
            $this->why instanceof Obstacle => $met($this->why),
            $this->alive => $alive(),
            $this->closed => $closed(),
            default => $nothing(),
        };
    }
}
