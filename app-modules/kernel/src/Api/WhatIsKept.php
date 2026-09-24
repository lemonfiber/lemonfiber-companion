<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Everything the stack keeps on this machine, configuration first and then what can be made again.
 *
 * @implements IteratorAggregate<int, SomethingKept>
 */
final readonly class WhatIsKept implements Countable, IteratorAggregate
{
    /** @param list<SomethingKept> $items */
    private function __construct(private array $items) {}

    /**
     * In the stack's order.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(SomethingKept ...$items): self
    {
        return new self(array_values($items));
    }

    /** @return Traversable<int, SomethingKept> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
