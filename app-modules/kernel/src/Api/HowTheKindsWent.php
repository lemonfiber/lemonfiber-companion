<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of asking what a member can search for and ask for: the core's list, or the reason there is none.
 */
final readonly class HowTheKindsWent
{
    private function __construct(private WhatCanBeAskedFor|Obstacle $answer) {}

    /** The core answered with its list. */
    public static function listed(WhatCanBeAskedFor $askable): self
    {
        return new self($askable);
    }

    /** It did not, and this is why. */
    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TListed of object
     * @template TRefused of object
     *
     * @param  Closure(WhatCanBeAskedFor): TListed  $listed
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TListed|TRefused
     */
    public function either(Closure $listed, Closure $refused): object
    {
        return $this->answer instanceof WhatCanBeAskedFor ? $listed($this->answer) : $refused($this->answer);
    }
}
