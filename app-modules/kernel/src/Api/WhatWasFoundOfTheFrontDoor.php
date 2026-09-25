<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The household's front door, or the reason it could not be read.
 *
 * The answer {@see Welcoming} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. A stack with no door comes back through the
 * first arm, standing `none`; one that could not be asked never does.
 */
final readonly class WhatWasFoundOfTheFrontDoor
{
    private function __construct(private TheFrontDoor|Obstacle $answer) {}

    /** The stack answered, and this is its door. */
    public static function found(TheFrontDoor $door): self
    {
        return new self($door);
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
     * @param Closure(TheFrontDoor): TFound $found
     * @param Closure(Obstacle): TMet       $met
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
