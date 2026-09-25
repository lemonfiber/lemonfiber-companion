<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where an item got to, or the reason it could not be followed.
 *
 * The answer {@see Tracing} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason.
 */
final readonly class WhatWasFoundOfTheTrace
{
    private function __construct(private WhereItGotTo|Obstacle $answer) {}

    public static function found(WhereItGotTo $trace): self
    {
        return new self($trace);
    }

    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TAnswered of object
     * @template TMet of object
     *
     * @param Closure(WhereItGotTo): TAnswered $found
     * @param Closure(Obstacle): TMet $met
     *
     * @return TAnswered|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $found($this->answer);
    }
}
