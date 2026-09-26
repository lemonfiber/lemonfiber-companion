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
 * Every port lemonfiber wants that something already here holds, in the stack's order.
 *
 * @implements IteratorAggregate<int, APortHeld>
 */
final readonly class ThePortsHeld implements Countable, IteratorAggregate
{
    /** @param list<APortHeld> $conflicts */
    private function __construct(private array $conflicts) {}

    /** These conflicts, in the stack's order; reindexed for a named spread's keys. */
    public static function of(APortHeld ...$conflicts): self
    {
        return new self(array_values($conflicts));
    }

    /** @return Traversable<int, APortHeld> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->conflicts);
    }

    public function count(): int
    {
        return count($this->conflicts);
    }
}
