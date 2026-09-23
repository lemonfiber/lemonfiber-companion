<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a machine keeps running on its own, or the reason there is no listing.
 *
 * The answer {@see Hosting} gives, and a value rather than an exception for
 * `C1`'s reason and {@see WhatIsStuck}'s: a stack that is asleep, one on
 * another network and one whose session has ended are ordinary states of the
 * world, and a method answering with a listing could report them only by
 * throwing.
 *
 * **The reason is an {@see Obstacle}**, the same set every other screen reads,
 * so the six situations have one vocabulary across the app.
 *
 * **A machine that hosts nothing is an answer, and so is one that cannot host.**
 * Both come back through the first arm, carrying a listing that says which they
 * are — a platform with no manager is a fact about the machine rather than a
 * failure to reach it, and folding it in with *this phone cannot see that
 * machine* would tell an operator to go and fix their network about a laptop
 * that was never going to run a launch agent.
 */
final readonly class WhatKeepsRunning
{
    private function __construct(private WhatRunsUnattended|Obstacle $answer) {}

    /** The machine answered, and this is what it keeps running. */
    public static function keeps(WhatRunsUnattended $running): self
    {
        return new self($running);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Both arms required, which is the argument {@see Launch::either()} makes:
     * an optional arm is a default, and a default is where an obstacle quietly
     * becomes *this machine hosts nothing* on a screen.
     *
     * @template TKeeps of object
     * @template TMet of object
     *
     * @param Closure(WhatRunsUnattended): TKeeps $keeps
     * @param Closure(Obstacle): TMet             $met
     *
     * @return TKeeps|TMet
     */
    public function either(Closure $keeps, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $keeps($this->answer);
    }
}
