<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What one member may watch, in the order the core listed it.
 *
 * A typed collection rather than an array, and the order is part of what it
 * carries: a shelf arrives ordered by whoever built it, and re-sorting here
 * would be this app deciding what a member sees first.
 *
 * **Empty is a legitimate value and is not the same as unread.** A member whose
 * shelf holds nothing has an answer; a shelf the media server would not hand
 * over has none. {@see WhatTheyMayWatch} is where those are kept apart, because
 * a screen drawing an empty list for the second one tells somebody their
 * library is empty when it is merely out of reach.
 *
 * @implements IteratorAggregate<int, Holding>
 */
final readonly class Shelf implements IteratorAggregate
{
    /** @param array<int, Holding> $holdings */
    private function __construct(private array $holdings) {}

    public static function of(Holding ...$holdings): self
    {
        // Not habit: a variadic collected from named arguments has string
        // keys, and everything below reads this by position.
        return new self(array_values($holdings));
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->holdings);
    }

    public function isEmpty(): bool
    {
        return $this->holdings === [];
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->holdings);
    }
}
