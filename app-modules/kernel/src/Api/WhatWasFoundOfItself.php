<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The running copy of lemonfiber, or the reason it could not be read.
 *
 * The answer {@see SelfChecking} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. The reason is an {@see Obstacle}, the set
 * every other screen reads.
 *
 * **An empty answer comes back through the first arm.** It and a stack that
 * could not be asked would otherwise both draw an empty list, and only one of
 * them is an answer.
 */
final readonly class WhatWasFoundOfItself
{
    private function __construct(private ThisCopyOfLemonfiber|Obstacle $answer) {}

    /** The stack answered, and this is what it said. */
    public static function found(ThisCopyOfLemonfiber $copy): self
    {
        return new self($copy);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Both arms required: an optional arm is a default, and a default is where
     * an obstacle quietly becomes an empty list on a screen.
     *
     * @template TAnswered of object
     * @template TMet of object
     *
     * @param Closure(ThisCopyOfLemonfiber): TAnswered $found
     * @param Closure(Obstacle): TMet $met
     *
     * @return TAnswered|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $found($this->answer);
    }
}
