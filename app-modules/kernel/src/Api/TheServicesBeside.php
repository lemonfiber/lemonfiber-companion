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
 * Everything else the household can reach, in the stack's order, and why none of it is the door.
 *
 * @implements IteratorAggregate<int, AServiceBeside>
 */
final readonly class TheServicesBeside implements Countable, IteratorAggregate
{
    /** @param list<AServiceBeside> $beside */
    private function __construct(private array $beside) {}

    /** These services, in the stack's order; reindexed for a named spread's keys. */
    public static function of(AServiceBeside ...$beside): self
    {
        return new self(array_values($beside));
    }

    /** @return Traversable<int, AServiceBeside> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->beside);
    }

    public function count(): int
    {
        return count($this->beside);
    }
}
