<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What asking a stack what it was told to leave alone came to: a list, a stack too old to say, or an obstacle. */
final readonly class WhatWasFoundOfTheAreasLeftAlone
{
    private function __construct(private TheAreasLeftAlone|Obstacle|null $answer) {}

    public static function listed(TheAreasLeftAlone $areas): self
    {
        return new self($areas);
    }

    public static function notCarried(): self
    {
        return new self(null);
    }

    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * @template TListed of object
     * @template TNotCarried of object
     * @template TMet of object
     *
     * @param  Closure(TheAreasLeftAlone): TListed  $listed
     * @param  Closure(): TNotCarried  $notCarried
     * @param  Closure(Obstacle): TMet  $met
     * @return TListed|TNotCarried|TMet
     */
    public function either(Closure $listed, Closure $notCarried, Closure $met): object
    {
        if ($this->answer instanceof Obstacle) {
            return $met($this->answer);
        }

        return $this->answer instanceof TheAreasLeftAlone ? $listed($this->answer) : $notCarried();
    }
}
