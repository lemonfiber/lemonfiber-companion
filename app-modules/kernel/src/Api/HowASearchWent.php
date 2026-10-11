<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What came of a member's search: a page, or the reason there is none.
 */
final readonly class HowASearchWent
{
    private function __construct(private WhatASearchFound|Obstacle $answer) {}

    /** The core answered with a page. */
    public static function found(WhatASearchFound $page): self
    {
        return new self($page);
    }

    /** It did not, and this is why. */
    public static function refused(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in both cases, and get back what you built.
     *
     * @template TFound of object
     * @template TRefused of object
     *
     * @param  Closure(WhatASearchFound): TFound  $found
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TFound|TRefused
     */
    public function either(Closure $found, Closure $refused): object
    {
        return $this->answer instanceof WhatASearchFound ? $found($this->answer) : $refused($this->answer);
    }
}
