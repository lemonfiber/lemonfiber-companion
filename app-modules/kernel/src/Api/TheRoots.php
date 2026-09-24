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
 * The directories everything the stack keeps sits under.
 *
 * @implements IteratorAggregate<int, WhereThingsAreKept>
 */
final readonly class TheRoots implements Countable, IteratorAggregate
{
    /** @param list<WhereThingsAreKept> $items */
    private function __construct(private array $items) {}

    /**
     * In the stack's order.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(WhereThingsAreKept ...$items): self
    {
        return new self(array_values($items));
    }

    /** @return Traversable<int, WhereThingsAreKept> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
