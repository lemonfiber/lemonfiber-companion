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
 * The moves in place on offer, in the stack's order.
 *
 * @implements IteratorAggregate<int, AMoveInPlace>
 */
final readonly class TheMovesInPlace implements Countable, IteratorAggregate
{
    /** @param list<AMoveInPlace> $moves */
    private function __construct(private array $moves) {}

    /** No move on offer. */
    public static function none(): self
    {
        return new self([]);
    }

    /** Each move, in order. */
    public static function these(AMoveInPlace ...$moves): self
    {
        return new self(array_values($moves));
    }

    /** @return Traversable<int, AMoveInPlace> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->moves);
    }

    public function count(): int
    {
        return count($this->moves);
    }
}
