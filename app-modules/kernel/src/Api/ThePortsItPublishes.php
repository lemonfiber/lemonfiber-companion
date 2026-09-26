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
 * The host ports one service already here publishes, in the stack's order.
 *
 * @implements IteratorAggregate<int, int>
 */
final readonly class ThePortsItPublishes implements Countable, IteratorAggregate
{
    /** @param list<int> $ports */
    private function __construct(private array $ports) {}

    /** These ports, as the stack listed them; reindexed for a named spread's keys. */
    public static function of(int ...$ports): self
    {
        return new self(array_values($ports));
    }

    /** @return Traversable<int, int> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->ports);
    }

    public function count(): int
    {
        return count($this->ports);
    }
}
