<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking for a title, or rehearsing it: the core's answer, or the reason there is none.
 */
final readonly class HowAnAskWent
{
    private function __construct(private WhatAnAskCameTo|Obstacle $answer) {}

    /** The core answered. */
    public static function answered(WhatAnAskCameTo $answer): self
    {
        return new self($answer);
    }

    /** It did not, and this is why. */
    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TAnswered of object
     * @template TRefused of object
     *
     * @param  Closure(WhatAnAskCameTo): TAnswered  $answered
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TAnswered|TRefused
     */
    public function either(Closure $answered, Closure $refused): object
    {
        return $this->answer instanceof WhatAnAskCameTo ? $answered($this->answer) : $refused($this->answer);
    }
}
