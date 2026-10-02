<?php

declare(strict_types=1);

namespace Modules\News\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * The items of one kind on one stack that are new to the operator, newest first.
 *
 * @implements IteratorAggregate<int, AnItem>
 */
final readonly class WhatIsNew implements Countable, IteratorAggregate
{
    /** @param list<AnItem> $items */
    private function __construct(private array $items) {}

    /** These, newest first. */
    public static function these(AnItem ...$items): self
    {
        return new self(array_values($items));
    }

    /** Nothing is new. */
    public static function nothing(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return Traversable<int, AnItem> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
