<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * What the stack says has stopped moving in its queue, worst first.
 *
 * The order is the stack's and is kept, not recomputed: the stack ranks by kind
 * and then by how long, and a second ranking here would be a second opinion
 * about which thing to fix first.
 *
 * Empty is a value, and says nothing has stopped.
 *
 * @implements IteratorAggregate<int, AStoppage>
 */
final readonly class WhatStoppedMoving implements IteratorAggregate
{
    /** @param list<AStoppage> $rows */
    private function __construct(private array $rows) {}

    /**
     * The rows in the order the stack sent them.
     *
     * Reindexed for {@see Stalled::of()}'s reason: a variadic collected from
     * named arguments has string keys, and everything reading this reads it
     * by position.
     */
    public static function of(AStoppage ...$rows): self
    {
        return new self(array_values($rows));
    }

    public static function nothing(): self
    {
        return new self([]);
    }

    /** @return Traversable<int, AStoppage> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rows);
    }
}
