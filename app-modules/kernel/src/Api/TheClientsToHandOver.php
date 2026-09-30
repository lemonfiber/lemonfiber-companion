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
 * Every app the stack names, open-source first, in its order.
 *
 * @implements IteratorAggregate<int, AClientToHandOver>
 */
final readonly class TheClientsToHandOver implements Countable, IteratorAggregate
{
    /** @param list<AClientToHandOver> $each */
    private function __construct(private array $each) {}

    /** These, in the stack's order. */
    public static function of(AClientToHandOver ...$each): self
    {
        return new self(array_values($each));
    }

    /** @return Traversable<int, AClientToHandOver> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->each);
    }

    public function count(): int
    {
        return count($this->each);
    }
}
