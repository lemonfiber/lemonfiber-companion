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
 * The wanted parts of a season that are not here yet, in the stack's order.
 *
 * @implements IteratorAggregate<int, AnEpisodeNotHereYet>
 */
final readonly class TheEpisodesNotHereYet implements Countable, IteratorAggregate
{
    /** @param list<AnEpisodeNotHereYet> $items */
    private function __construct(private array $items) {}

    /**
     * In the order given.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(AnEpisodeNotHereYet ...$items): self
    {
        return new self(array_values($items));
    }

    /** @return Traversable<int, AnEpisodeNotHereYet> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
