<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * How long a verb takes something away for, as the stack reported it.
 *
 * `N2-R8` wants a disruptive action to state the bound the stack gave, or that
 * it gave none, before the operator confirms — and `N2-R14` forbids this side
 * inventing one. Two shapes, because *no bound* is not a long bound: a surface
 * handed a number plus a "really, though?" beside it will show the number.
 *
 * **There is no third case for *the stack said nothing*.** A verb this side
 * cannot read a bound for is a payload gone wrong, not a verb that costs
 * nothing, and the reader refuses rather than reaching for a case here.
 */
final readonly class WhatItTakesAway
{
    /**
     * One field, because this is one of two things rather than two things one
     * of which is absent.
     *
     * Two nullable fields would need a number in the unbounded case, and there
     * is no number: whatever went there — nought, minus one — would be a value
     * nothing can read and nothing can be wrong about, which is how a
     * placeholder outlives the reason for it. A union holds exactly the two
     * states this has and leaves nowhere to put a third.
     */
    private function __construct(private int|Awaiting $held) {}

    /** It ends, and this is the longest the run is held to. */
    public static function atMost(int $seconds): self
    {
        return new self($seconds);
    }

    /** Nothing bounds it, and this is what it waits for. */
    public static function until(Awaiting $awaiting): self
    {
        return new self($awaiting);
    }

    /**
     * Say the length, or say what it is waiting for instead.
     *
     * Two arms rather than a nullable getter, for the reason
     * {@see Daemon::exit()} gives — and here the stakes are `N2-R14`'s: a
     * screen handed a zero would render *nought seconds*, which is a promise
     * no run can keep and the operator would read as *this is instant*.
     *
     * @template TBounded of object
     * @template TOpen of object
     *
     * @param  Closure(int): TBounded  $bounded
     * @param  Closure(Awaiting): TOpen  $openEnded
     * @return TBounded|TOpen
     */
    public function either(Closure $bounded, Closure $openEnded): object
    {
        return $this->held instanceof Awaiting
            ? $openEnded($this->held)
            : $bounded($this->held);
    }
}
