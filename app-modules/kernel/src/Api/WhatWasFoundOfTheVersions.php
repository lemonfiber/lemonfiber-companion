<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Which versions a stack runs, or the reason they could not be read.
 *
 * The answer {@see ReadingVersions} gives, and a value rather than an exception
 * for {@see WhatWasRecorded}'s reason. The reason is an {@see Obstacle}, the set
 * every other screen reads.
 */
final readonly class WhatWasFoundOfTheVersions
{
    private function __construct(private WhatRunsHere|Obstacle $answer) {}

    /** The stack answered, and this is what it said. */
    public static function found(WhatRunsHere $runs): self
    {
        return new self($runs);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Both arms required, for {@see WhatWasFoundOfItself::either()}'s reason.
     *
     * @template TAnswered of object
     * @template TMet of object
     *
     * @param Closure(WhatRunsHere): TAnswered $found
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
