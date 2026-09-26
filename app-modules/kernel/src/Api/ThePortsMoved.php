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
 * Where each lemonfiber service would listen to run beside what is here, in the stack's order.
 *
 * @implements IteratorAggregate<int, APortMoved>
 */
final readonly class ThePortsMoved implements Countable, IteratorAggregate
{
    /** @param list<APortMoved> $moved */
    private function __construct(private array $moved) {}

    /** These services, in the stack's order; reindexed for a named spread's keys. */
    public static function of(APortMoved ...$moved): self
    {
        return new self(array_values($moved));
    }

    /** @return Traversable<int, APortMoved> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->moved);
    }

    public function count(): int
    {
        return count($this->moved);
    }
}
