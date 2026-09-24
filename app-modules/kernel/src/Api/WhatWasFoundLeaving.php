<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What leaves a machine, or the reason that could not be learned.
 *
 * The answer {@see Outgoing} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason.
 *
 * **Nothing leaving comes back through the first arm.** A stack that sends
 * nothing and a stack that could not be asked both draw an empty list, and
 * only one of them is the answer somebody checking their privacy wants.
 */
final readonly class WhatWasFoundLeaving
{
    private function __construct(private WhatLeavesThisMachine|Obstacle $answer) {}

    /** The stack answered, and this is what leaves it. */
    public static function leaving(WhatLeavesThisMachine $leaving): self
    {
        return new self($leaving);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TLeaving of object
     * @template TMet of object
     *
     * @param Closure(WhatLeavesThisMachine): TLeaving $leaving
     * @param Closure(Obstacle): TMet                  $met
     *
     * @return TLeaving|TMet
     */
    public function either(Closure $leaving, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $leaving($this->answer);
    }
}
