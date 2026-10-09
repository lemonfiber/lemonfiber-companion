<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking a stack for a grant to play.
 *
 * A grant, or the reason there is none.
 */
final readonly class WhatTheGrantCameTo
{
    private function __construct(private AGrant|Obstacle $answer) {}

    /** The core granted it. */
    public static function granted(AGrant $grant): self
    {
        return new self($grant);
    }

    /** It did not, and this is why. */
    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TGranted of object
     * @template TRefused of object
     *
     * @param  Closure(AGrant): TGranted  $granted
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TGranted|TRefused
     */
    public function either(Closure $granted, Closure $refused): object
    {
        return $this->answer instanceof AGrant ? $granted($this->answer) : $refused($this->answer);
    }
}
