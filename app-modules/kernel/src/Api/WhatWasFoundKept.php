<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Everything the stack keeps on this machine, or the reason that could not be learned.
 *
 * The answer {@see Storing} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. The reason is an {@see Obstacle}, the set
 * every other screen reads.
 *
 * **An empty answer comes back through the first arm.** It and a stack that
 * could not be asked would otherwise both draw an empty list, and only one of
 * them is an answer.
 */
final readonly class WhatWasFoundKept
{
    private function __construct(private WhatThisMachineKeeps|Obstacle $answer) {}

    /** The stack answered, and this is what it said. */
    public static function kept(WhatThisMachineKeeps $kept): self
    {
        return new self($kept);
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
     * @param Closure(WhatThisMachineKeeps): TAnswered $kept
     * @param Closure(Obstacle): TMet $met
     *
     * @return TAnswered|TMet
     */
    public function either(Closure $kept, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $kept($this->answer);
    }
}
