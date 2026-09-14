<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What the household asked for, or the reason there is no list.
 *
 * The answer {@see Wanting} gives, and a value rather than an exception for
 * `C1`'s reason and {@see WhatCameBack}'s: a stack that is asleep, one on
 * another network and one whose session has ended are ordinary states of the
 * world, and a method answering with a list could report them only by throwing.
 *
 * **The reason is an {@see Obstacle}**, the same set every other screen reads.
 * An operator meets the same six situations whether they were signing in,
 * asking after the machine or asking what the house wants, and a second
 * vocabulary for *why you cannot see this right now* would be a second set of
 * sentences to write, translate and keep in step.
 *
 * **An empty list is an answer, not an absence.** A household that has asked
 * for nothing is the ordinary state of a quiet week, and it is told apart from
 * a stack that could not be asked — which is the distinction that makes the
 * screen worth opening at all. Folding the two together would have a phone in
 * flight mode say *nobody has asked for anything*.
 */
final readonly class WhatWasWanted
{
    private function __construct(private Requested|Obstacle $answer) {}

    /** The stack answered, and this is what the house has asked for. */
    public static function these(Requested $wanted): self
    {
        return new self($wanted);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * Both arms required, which is the same argument {@see Launch::either()}
     * makes: an optional arm is a default, and a default is where an obstacle
     * quietly becomes an empty list on a screen.
     *
     * @template TThese of object
     * @template TMet of object
     *
     * @param Closure(Requested): TThese $these
     * @param Closure(Obstacle): TMet       $met
     *
     * @return TThese|TMet
     */
    public function either(Closure $these, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $these($this->answer);
    }
}
