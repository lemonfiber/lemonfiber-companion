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
 * The volumes the stack watches, in the order it reports them.
 *
 * @implements IteratorAggregate<int, AVolume>
 */
final readonly class TheVolumes implements Countable, IteratorAggregate
{
    /** @param list<AVolume> $items */
    private function __construct(private array $items) {}

    /**
     * In the stack's order.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(AVolume ...$items): self
    {
        return new self(array_values($items));
    }

    /** @return Traversable<int, AVolume> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
