<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * How a stack shares its line, or the reason that could not be learned.
 *
 * The answer {@see Rationing} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. A line that could not be read is never an
 * unlimited one.
 */
final readonly class WhatTheLineWasFound
{
    private function __construct(private HowTheLineIsShared|Obstacle $answer) {}

    /** The stack answered, and this is how its line is shared. */
    public static function shared(HowTheLineIsShared $shared): self
    {
        return new self($shared);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TShared of object
     * @template TMet of object
     *
     * @param Closure(HowTheLineIsShared): TShared $shared
     * @param Closure(Obstacle): TMet              $met
     *
     * @return TShared|TMet
     */
    public function either(Closure $shared, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $shared($this->answer);
    }
}
