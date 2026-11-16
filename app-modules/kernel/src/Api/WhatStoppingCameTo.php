<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What came of telling the core a live stream stopped: it closed it, or something stood in the way. */
final readonly class WhatStoppingCameTo
{
    private function __construct(private ?Obstacle $why) {}

    public static function stopped(): self
    {
        return new self(null);
    }

    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TStopped of object
     * @template TRefused of object
     *
     * @param Closure(): TStopped         $stopped
     * @param Closure(Obstacle): TRefused $refused
     *
     * @return TStopped|TRefused
     */
    public function either(Closure $stopped, Closure $refused): object
    {
        return $this->why instanceof Obstacle ? $refused($this->why) : $stopped();
    }
}
