<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of telling a stack where a member is: the place it kept, or why not.
 */
final readonly class WhatThePlaceCameTo
{
    private function __construct(private ThePlace|Obstacle $answer) {}

    /** The core keeps this place. */
    public static function kept(ThePlace $place): self
    {
        return new self($place);
    }

    /** It did not, and this is why. */
    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TKept of object
     * @template TRefused of object
     *
     * @param  Closure(ThePlace): TKept  $kept
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TKept|TRefused
     */
    public function either(Closure $kept, Closure $refused): object
    {
        return $this->answer instanceof ThePlace ? $kept($this->answer) : $refused($this->answer);
    }
}
