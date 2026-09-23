<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * Everything one reading found that the stack cannot act on.
 *
 * A type rather than an array, which is `D1`, and unsorted: the order is the
 * stack's and any order imposed here would be an opinion about which limit
 * matters most, which is a judgement nobody sent.
 *
 * **Empty is the ordinary answer and has to be a value.** A reading where the
 * stack can act on everything says so by listing nothing, and that must not
 * arrive as an absence — *nothing was unsupported* and *nobody said* are the
 * two answers a screen must never confuse, and a collection that could only be
 * missing would make them the same. {@see WhatNothingFills} makes the argument
 * at more length.
 *
 * @implements IteratorAggregate<int, Unsupported>
 */
final readonly class WhatIsUnsupported implements IteratorAggregate
{
    /** @param list<Unsupported> $limits */
    private function __construct(private array $limits) {}

    /**
     * The limits a reading carried, in the order it carried them.
     *
     * Reindexed for {@see Services::these()}'s reason: a variadic collected
     * from named arguments carries their names as keys, so being variadic is
     * not the same claim as being a list.
     */
    public static function these(Unsupported ...$limits): self
    {
        return new self(array_values($limits));
    }

    /** The stack can act on everything here, which is an answer rather than a silence. */
    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->limits);
    }

    /** @return Traversable<int, Unsupported> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->limits);
    }
}
