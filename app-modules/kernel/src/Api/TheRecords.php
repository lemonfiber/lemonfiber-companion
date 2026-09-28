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
 * Records an import carried across, or would, in the stack's order.
 *
 * @implements IteratorAggregate<int, ARecord>
 */
final readonly class TheRecords implements Countable, IteratorAggregate
{
    /** @param list<ARecord> $records */
    private function __construct(private array $records) {}

    /** These records, in the stack's order; reindexed for a named spread's keys. */
    public static function of(ARecord ...$records): self
    {
        return new self(array_values($records));
    }

    /** @return Traversable<int, ARecord> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->records);
    }

    public function count(): int
    {
        return count($this->records);
    }
}
