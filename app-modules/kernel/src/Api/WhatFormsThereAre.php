<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The forms a stack has, or the reason they could not be listed.
 *
 * Every form, whether or not anything in it is running: what is asked for is
 * start, stop and restart *by form* as well as by service, and a stack whose
 * `library` form is wholly stopped has a form an operator wants to start, which
 * a list derived from what runs would lose.
 *
 * A value rather than an exception for `C1`'s reason, which
 * {@see WhatIsRunning} gives.
 */
final readonly class WhatFormsThereAre
{
    private function __construct(private Forms|Obstacle $answer) {}

    /** The stack listed its forms, which may be none. */
    public static function these(Forms $forms): self
    {
        return new self($forms);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TThese of object
     * @template TMet of object
     *
     * @param Closure(Forms): TThese  $these
     * @param Closure(Obstacle): TMet $met
     *
     * @return TThese|TMet
     */
    public function either(Closure $these, Closure $met): object
    {
        return $this->answer instanceof Forms ? $these($this->answer) : $met($this->answer);
    }
}
