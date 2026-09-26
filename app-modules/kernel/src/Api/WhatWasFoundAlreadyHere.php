<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack found already on its machine, or the reason it could not be asked.
 *
 * The answer {@see MovingIn} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. A survey whose engine could not look comes
 * back through the first arm, saying so; a stack that could not be asked never
 * does.
 */
final readonly class WhatWasFoundAlreadyHere
{
    private function __construct(private TheSurvey|Obstacle $answer) {}

    /** The stack answered, and this is what it found. */
    public static function found(TheSurvey $survey): self
    {
        return new self($survey);
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
     * @param Closure(TheSurvey): TFound $found
     * @param Closure(Obstacle): TMet    $met
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
