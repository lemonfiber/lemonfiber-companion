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
 * Each move in place an update tried, in the stack's order.
 *
 * @implements IteratorAggregate<int, WhatBecameOfAMoveInPlace>
 */
final readonly class WhatBecameOfTheMovesInPlace implements Countable, IteratorAggregate
{
    /** @param list<WhatBecameOfAMoveInPlace> $moves */
    private function __construct(private array $moves) {}

    /** No move tried. */
    public static function none(): self
    {
        return new self([]);
    }

    /** Each move, in order. */
    public static function these(WhatBecameOfAMoveInPlace ...$moves): self
    {
        return new self(array_values($moves));
    }

    /** @return Traversable<int, WhatBecameOfAMoveInPlace> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->moves);
    }

    public function count(): int
    {
        return count($this->moves);
    }
}
