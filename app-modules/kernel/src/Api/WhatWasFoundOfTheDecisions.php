<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** What asking a stack for the decisions setup makes came to. */
final readonly class WhatWasFoundOfTheDecisions
{
    private function __construct(private WhatSetupDecided|Obstacle $answer) {}

    public static function found(WhatSetupDecided $decided): self
    {
        return new self($decided);
    }

    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * @template TFound of object
     * @template TMet of object
     *
     * @param  Closure(WhatSetupDecided): TFound  $found
     * @param  Closure(Obstacle): TMet  $met
     * @return TFound|TMet
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $found($this->answer);
    }
}
