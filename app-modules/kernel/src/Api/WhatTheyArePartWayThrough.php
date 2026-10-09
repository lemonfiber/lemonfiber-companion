<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking what a member was part-way through: the core's answer, or what stood in the way.
 */
final readonly class WhatTheyArePartWayThrough
{
    private function __construct(private PartWays|Obstacle $answer) {}

    public static function told(PartWays $partWay): self
    {
        return new self($partWay);
    }

    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TTold of object
     * @template TRefused of object
     *
     * @param  Closure(PartWays): TTold  $told
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TTold|TRefused
     */
    public function either(Closure $told, Closure $refused): object
    {
        return $this->answer instanceof PartWays ? $told($this->answer) : $refused($this->answer);
    }
}
