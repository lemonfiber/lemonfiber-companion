<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the stack said of how it is served, or the reason that could not be learned.
 */
final readonly class WhatWasSaidOfServing
{
    private function __construct(private HowItIsServed|Obstacle $answer) {}

    /** The stack answered. */
    public static function said(HowItIsServed $served): self
    {
        return new self($served);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TSaid of object
     * @template TMet of object
     *
     * @param Closure(HowItIsServed): TSaid $said
     * @param Closure(Obstacle): TMet       $met
     *
     * @return TSaid|TMet
     */
    public function either(Closure $said, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $said($this->answer);
    }
}
