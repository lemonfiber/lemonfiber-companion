<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Which app to watch on, or the reason the advice could not be read.
 *
 * The answer {@see Advising} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason.
 */
final readonly class WhatWasFoundToWatchOn
{
    private function __construct(private WhatToWatchOn|Obstacle $answer) {}

    /** The stack answered, and this is its advice. */
    public static function found(WhatToWatchOn $advice): self
    {
        return new self($advice);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TFound of object
     * @template TMet of object
     *
     * @param Closure(WhatToWatchOn): TFound $found
     * @param Closure(Obstacle): TMet        $met
     *
     * @return TFound|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $found($this->answer);
    }
}
