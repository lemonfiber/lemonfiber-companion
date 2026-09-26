<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The quality in force, or the reason it could not be read.
 *
 * The answer {@see ChoosingQuality::inForceOn()} gives, and a value rather
 * than an exception for {@see WhatWasRecorded}'s reason.
 */
final readonly class WhatWasFoundOfTheQuality
{
    private function __construct(private TheQualityChosen|Obstacle $answer) {}

    /** The stack answered, and this is what is in force. */
    public static function found(TheQualityChosen $chosen): self
    {
        return new self($chosen);
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
     * @param Closure(TheQualityChosen): TFound $found
     * @param Closure(Obstacle): TMet           $met
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
