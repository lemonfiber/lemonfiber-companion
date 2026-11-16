<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** The guide one member may read, or why the core would not say. */
final readonly class WhatTheGuideSays
{
    private function __construct(private TheGuide|Obstacle $answer) {}

    public static function told(TheGuide $guide): self
    {
        return new self($guide);
    }

    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TTold of object
     * @template TRefused of object
     *
     * @param Closure(TheGuide): TTold    $told
     * @param Closure(Obstacle): TRefused $refused
     *
     * @return TTold|TRefused
     */
    public function either(Closure $told, Closure $refused): object
    {
        return $this->answer instanceof Obstacle ? $refused($this->answer) : $told($this->answer);
    }
}
