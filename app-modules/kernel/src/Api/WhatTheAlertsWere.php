<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the operator is told about, or the reason that could not be learned.
 *
 * The answer {@see Telling} gives, and a value rather than an exception for
 * {@see WhatWasRecorded}'s reason.
 */
final readonly class WhatTheAlertsWere
{
    private function __construct(private WhatTheOperatorIsTold|Obstacle $answer) {}

    /** The stack answered, and this is what the operator is told about. */
    public static function told(WhatTheOperatorIsTold $told): self
    {
        return new self($told);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TTold of object
     * @template TMet of object
     *
     * @param Closure(WhatTheOperatorIsTold): TTold $told
     * @param Closure(Obstacle): TMet               $met
     *
     * @return TTold|TMet
     */
    public function either(Closure $told, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $told($this->answer);
    }
}
