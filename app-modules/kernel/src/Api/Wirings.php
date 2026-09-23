<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What reaches what, in the order the core listed it.
 *
 * A type rather than an array, which is `D1`, and unsorted for
 * {@see WhatNothingFills}'s reason: an order imposed here would be an opinion
 * about which connection matters most, and the core sent none.
 *
 * @implements IteratorAggregate<int, Wiring>
 */
final readonly class Wirings implements IteratorAggregate
{
    /** @param list<Wiring> $wirings */
    private function __construct(private array $wirings) {}

    /** The wirings, as the core listed them. */
    public static function these(Wiring ...$wirings): self
    {
        return new self(array_values($wirings));
    }

    /** Nothing reaches anything, which a stack with one service can honestly answer. */
    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->wirings);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->wirings);
    }
}
