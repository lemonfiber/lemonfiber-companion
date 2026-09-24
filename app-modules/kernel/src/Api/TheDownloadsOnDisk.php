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
 * The completed downloads on the machine, in the order the stack gives them.
 *
 * @implements IteratorAggregate<int, ADownloadOnDisk>
 */
final readonly class TheDownloadsOnDisk implements Countable, IteratorAggregate
{
    /** @param list<ADownloadOnDisk> $items */
    private function __construct(private array $items) {}

    /**
     * In the stack's order.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(ADownloadOnDisk ...$items): self
    {
        return new self(array_values($items));
    }

    /** @return Traversable<int, ADownloadOnDisk> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
