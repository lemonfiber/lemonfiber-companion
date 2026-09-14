<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack is running, or the reason there is no listing.
 *
 * The answer {@see Supervising} gives, and a value rather than an exception for
 * `C1`'s reason and {@see WhatIsStuck}'s: a stack that is asleep, one on
 * another network and one whose session has ended are ordinary states of the
 * world, and a method answering with a listing could report them only by
 * throwing.
 *
 * **The reason is an {@see Obstacle}**, the same set every other screen reads,
 * so the six situations have one vocabulary across the app.
 *
 * **A stack running nothing is an answer.** `Inactive` is a state an operator
 * acts on — everything is off, and the thing to do is start something — and it
 * must not fold together with *this phone cannot reach the machine*, which is
 * the answer that looks identical on a screen and means the opposite.
 */
final readonly class WhatIsRunning
{
    private function __construct(private Daemons|Obstacle $answer) {}

    /** The stack answered, and this is what it is running. */
    public static function these(Daemons $daemons): self
    {
        return new self($daemons);
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
     * becomes an empty list — here, a stack that looks switched off.
     *
     * @template TThese of object
     * @template TMet of object
     *
     * @param  Closure(Daemons): TThese $these
     * @param  Closure(Obstacle): TMet  $met
     * @return TThese|TMet
     */
    public function either(Closure $these, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $these($this->answer);
    }
}
