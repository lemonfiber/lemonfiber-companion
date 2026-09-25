<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The credentials a stack holds, or the reason they could not be read.
 *
 * The answer {@see Safekeeping} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. A stack holding no credentials comes back
 * through the first arm with an empty list; one that could not be asked never
 * does.
 */
final readonly class WhatWasFoundOfTheCredentials
{
    private function __construct(private TheCredentialsHeld|Obstacle $answer) {}

    /** The stack answered, and these are what it holds. */
    public static function found(TheCredentialsHeld $held): self
    {
        return new self($held);
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
     * @param Closure(TheCredentialsHeld): TFound $found
     * @param Closure(Obstacle): TMet             $met
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
