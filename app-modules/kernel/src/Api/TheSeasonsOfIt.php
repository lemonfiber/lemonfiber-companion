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
 * How much of each season of a traced series is here, in order.
 *
 * @implements IteratorAggregate<int, HowMuchOfASeasonIsHere>
 */
final readonly class TheSeasonsOfIt implements Countable, IteratorAggregate
{
    /** @param list<HowMuchOfASeasonIsHere> $items */
    private function __construct(private array $items) {}

    /**
     * In the order given.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(HowMuchOfASeasonIsHere ...$items): self
    {
        return new self(array_values($items));
    }

    /** @return Traversable<int, HowMuchOfASeasonIsHere> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
