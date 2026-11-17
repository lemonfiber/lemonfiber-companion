<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** The channels one member may watch, or why the core would not say. */
final readonly class WhatIsOnTheChannels
{
    private function __construct(private TheChannels|Obstacle $answer) {}

    public static function told(TheChannels $channels): self
    {
        return new self($channels);
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
     * @param Closure(TheChannels): TTold $told
     * @param Closure(Obstacle): TRefused $refused
     *
     * @return TTold|TRefused
     */
    public function either(Closure $told, Closure $refused): object
    {
        return $this->answer instanceof Obstacle ? $refused($this->answer) : $told($this->answer);
    }
}
