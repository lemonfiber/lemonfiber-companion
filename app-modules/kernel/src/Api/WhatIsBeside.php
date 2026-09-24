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
 * What is on this machine that is not the stack's to keep or remove.
 *
 * @implements IteratorAggregate<int, SomethingBeside>
 */
final readonly class WhatIsBeside implements Countable, IteratorAggregate
{
    /** @param list<SomethingBeside> $items */
    private function __construct(private array $items) {}

    /**
     * In the stack's order.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(SomethingBeside ...$items): self
    {
        return new self(array_values($items));
    }

    /** @return Traversable<int, SomethingBeside> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
