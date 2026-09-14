<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What a stack says has stopped coming in, and whether that is all of it.
 *
 * A typed collection rather than an array, which is `D1` and which makes the
 * order part of the type's promise. The order is the stack's own, which is the
 * order the work was queued in — and that is information: the oldest thing
 * stuck is usually the one that has been wrong longest.
 *
 * **How much is shown comes first.** `stuck.incomplete` is a fact about the
 * listing rather than about any row in it, and putting it before the variadic
 * means a caller building one has decided about it before naming a single item.
 * A listing that could be built from rows alone is a listing something will one
 * day build from rows alone, and the screen it feeds will claim to be complete
 * without anybody choosing that.
 *
 * Empty is a legitimate value and the answer the operator wants most: nothing
 * has stopped. It is told apart from a stack that could not be asked by
 * {@see WhatIsStuck}, not here — an empty collection says *nothing is stuck*
 * without a null anywhere (`C2`).
 *
 * @implements IteratorAggregate<int, Stuck>
 */
final readonly class Stalled implements IteratorAggregate
{
    /** @param array<int, Stuck> $items */
    private function __construct(
        private HowMuchIsShown $shown,
        private array $items,
    ) {}

    /**
     * A listing as one stack gave it, with how much of it this is.
     *
     * Not habit: a variadic collected from named arguments has string keys, and
     * everything below reads this by position — see {@see Requested::of()},
     * where the same reindex is the same decision.
     */
    public static function of(HowMuchIsShown $shown, Stuck ...$items): self
    {
        return new self($shown, array_values($items));
    }

    /** Nothing has stopped, and the stack said so about everything it has. */
    public static function nothing(): self
    {
        return new self(HowMuchIsShown::AllOfIt, []);
    }

    /**
     * Whether this is the whole of what the stack has.
     *
     * Published rather than folded into the rows because it is the sentence a
     * screen has to put beside them, and a screen cannot ask a row about it.
     */
    public function howMuchIsShown(): HowMuchIsShown
    {
        return $this->shown;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return Traversable<int, Stuck> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
