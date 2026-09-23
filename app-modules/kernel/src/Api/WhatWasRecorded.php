<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * A stack's record, or the reason there is none.
 *
 * The answer {@see History} gives, and a value rather than an exception for
 * `C1`'s reason and {@see WhatKeepsRunning}'s. The reason is an
 * {@see Obstacle}, the set every other screen reads.
 *
 * **An empty record comes back through the first arm.** A stack that has
 * changed nothing and a stack that could not be asked both draw an empty list,
 * and only one of them means nothing happened — which is the reason the two
 * cannot share an arm.
 */
final readonly class WhatWasRecorded
{
    private function __construct(private TheRecord|Obstacle $answer) {}

    /** The stack answered, and this is what it has changed. */
    public static function record(TheRecord $record): self
    {
        return new self($record);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Both arms required, which is the argument {@see Launch::either()} makes:
     * an optional arm is a default, and a default is where an obstacle quietly
     * becomes *nothing was changed* on a screen.
     *
     * @template TRecord of object
     * @template TMet of object
     *
     * @param Closure(TheRecord): TRecord $record
     * @param Closure(Obstacle): TMet     $met
     *
     * @return TRecord|TMet
     */
    public function either(Closure $record, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $record($this->answer);
    }
}
