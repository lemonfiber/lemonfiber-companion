<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the stack said of its last start, or the reason that could not be learned.
 *
 * The answer {@see AskingHowItCameBack} gives, and a value rather than an
 * exception for {@see WhatWasRecorded}'s reason.
 */
final readonly class HowItCameBack
{
    private function __construct(private WhatTheLastStartCameTo|Obstacle $answer) {}

    /** The stack answered. */
    public static function said(WhatTheLastStartCameTo $said): self
    {
        return new self($said);
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
     * @param Closure(WhatTheLastStartCameTo): TSaid $said
     * @param Closure(Obstacle): TMet                $met
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
