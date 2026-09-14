<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What has stopped coming in, or the reason there is no list.
 *
 * The answer {@see Stalling} gives, and a value rather than an exception for
 * `C1`'s reason and {@see WhatWasWanted}'s: a stack that is asleep, one on
 * another network and one whose session has ended are ordinary states of the
 * world, and a method answering with a listing could report them only by
 * throwing.
 *
 * **The reason is an {@see Obstacle}**, the same set every other screen reads.
 * An operator meets the same six situations whether they were signing in,
 * asking after the machine or asking what has stalled, and a second vocabulary
 * for *why you cannot see this right now* would be a second set of sentences to
 * write, translate and keep in step.
 *
 * **An empty listing is an answer, not an absence**, and here it is the answer
 * the operator most wants: nothing has stopped. Folding it together with a
 * stack that could not be asked would have a phone in flight mode report a
 * house where everything is arriving normally, which is the one wrong answer
 * this screen can give that nobody would go and check.
 */
final readonly class WhatIsStuck
{
    private function __construct(private Stalled|Obstacle $answer) {}

    /** The stack answered, and this is what has stopped. */
    public static function these(Stalled $stalled): self
    {
        return new self($stalled);
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
     * becomes an empty list on a screen.
     *
     * @template TThese of object
     * @template TMet of object
     *
     * @param Closure(Stalled): TThese $these
     * @param Closure(Obstacle): TMet  $met
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
