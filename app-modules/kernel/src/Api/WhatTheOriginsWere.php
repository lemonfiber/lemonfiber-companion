<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where a stack's services come from, or the reason that could not be learned.
 *
 * The answer {@see Provenance} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason. The reason is an {@see Obstacle}, the set
 * every other screen reads.
 *
 * **A stack declaring no services comes back through the first arm.** It and a
 * stack that could not be asked both draw an empty list, and only one of them
 * is an answer.
 */
final readonly class WhatTheOriginsWere
{
    private function __construct(private WhereTheServicesComeFrom|Obstacle $answer) {}

    /** The stack answered, and this is where its services come from. */
    public static function origins(WhereTheServicesComeFrom $origins): self
    {
        return new self($origins);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Both arms required, for {@see WhatWasRecorded::either()}'s reason: an
     * optional arm is a default, and a default is where an obstacle quietly
     * becomes *this stack runs nothing* on a screen.
     *
     * @template TOrigins of object
     * @template TMet of object
     *
     * @param Closure(WhereTheServicesComeFrom): TOrigins $origins
     * @param Closure(Obstacle): TMet                     $met
     *
     * @return TOrigins|TMet
     */
    public function either(Closure $origins, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $origins($this->answer);
    }
}
