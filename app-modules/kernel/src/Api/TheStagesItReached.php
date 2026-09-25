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
 * The stages a traced item passed through, in order.
 *
 * @implements IteratorAggregate<int, AStageItReached>
 */
final readonly class TheStagesItReached implements Countable, IteratorAggregate
{
    /** @param list<AStageItReached> $items */
    private function __construct(private array $items) {}

    /**
     * In the order given.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(AStageItReached ...$items): self
    {
        return new self(array_values($items));
    }

    /** @return Traversable<int, AStageItReached> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
