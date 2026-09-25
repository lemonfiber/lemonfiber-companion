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
 * The notable events in a traced item's history, oldest first: a repeated attempt shows as the pattern it is.
 *
 * @implements IteratorAggregate<int, AMomentInItsHistory>
 */
final readonly class TheMomentsInItsHistory implements Countable, IteratorAggregate
{
    /** @param list<AMomentInItsHistory> $items */
    private function __construct(private array $items) {}

    /**
     * In the order given.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(AMomentInItsHistory ...$items): self
    {
        return new self(array_values($items));
    }

    /** @return Traversable<int, AMomentInItsHistory> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
