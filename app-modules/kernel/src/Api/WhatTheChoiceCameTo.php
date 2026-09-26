<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What asking a stack to choose a quality came to, or the reason the asking did not complete.
 *
 * Three arms, because the stack answers a preset with the whole of what is in
 * force and a format for music with what its service made of it. The refused
 * arm says the asking did not complete, and deliberately not that the choice
 * was not made, for {@see WhatTheStackMadeOfIt}'s reason.
 */
final readonly class WhatTheChoiceCameTo
{
    private function __construct(private TheQualityChosen|AFormatChoiceMade|Obstacle $answer) {}

    /** The stack answered with the quality in force. */
    public static function inForce(TheQualityChosen $chosen): self
    {
        return new self($chosen);
    }

    /** The stack answered with what choosing a format for music did. */
    public static function forMusic(AFormatChoiceMade $made): self
    {
        return new self($made);
    }

    /** The asking did not complete, and this is what stood in the way. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TInForce of object
     * @template TMusic of object
     * @template TMet of object
     *
     * @param Closure(TheQualityChosen): TInForce $inForce
     * @param Closure(AFormatChoiceMade): TMusic  $forMusic
     * @param Closure(Obstacle): TMet             $met
     *
     * @return TInForce|TMusic|TMet
     */
    public function either(Closure $inForce, Closure $forMusic, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof Obstacle => $met($this->answer),
            $this->answer instanceof AFormatChoiceMade => $forMusic($this->answer),
            default => $inForce($this->answer),
        };
    }
}
