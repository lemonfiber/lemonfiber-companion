<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What one service has been saying, or the reason there is no window.
 *
 * The answer {@see Saying} gives, and a value rather than an exception for
 * `C1`'s reason and {@see WhatIsStuck}'s: a stack that is asleep, one on
 * another network and one whose session has ended are ordinary states of the
 * world, and a method answering with a window could report them only by
 * throwing.
 *
 * **The reason is an {@see Obstacle}**, the same set every other screen reads.
 * An operator meets the same six situations whether they were signing in,
 * asking after the machine or reading a service's logs, and a second vocabulary
 * for *why you cannot see this right now* would be a second set of sentences to
 * write, translate and keep in step.
 *
 * **A window of no lines is an answer.** A service that has said nothing in the
 * tail that was asked for is quiet, not unreachable — and the two must not fold
 * together, because *this service has been silent* is a finding an operator
 * acts on and *your phone is on the wrong network* is not.
 */
final readonly class WhatWasSaid
{
    private function __construct(private Scrollback|Obstacle $answer) {}

    /** The stack answered, and this is the window it gave. */
    public static function this(Scrollback $scrollback): self
    {
        return new self($scrollback);
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
     * becomes an empty screen that reads as a silent service.
     *
     * @template TThis of object
     * @template TMet of object
     *
     * @param  Closure(Scrollback): TThis $this_
     * @param  Closure(Obstacle): TMet    $met
     * @return TThis|TMet
     */
    public function either(Closure $this_, Closure $met): object
    {
        return $this->answer instanceof Obstacle
            ? $met($this->answer)
            : $this_($this->answer);
    }
}
