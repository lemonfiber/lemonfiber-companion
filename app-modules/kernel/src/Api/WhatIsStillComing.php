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
 * Every download still coming down, which stopping would interrupt.
 *
 * @implements IteratorAggregate<int, SomethingStillComing>
 */
final readonly class WhatIsStillComing implements Countable, IteratorAggregate
{
    /** @param list<SomethingStillComing> $coming */
    private function __construct(private array $coming) {}

    /** These, in the stack's order. */
    public static function of(SomethingStillComing ...$coming): self
    {
        return new self(array_values($coming));
    }

    /** @return Traversable<int, SomethingStillComing> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->coming);
    }

    public function count(): int
    {
        return count($this->coming);
    }
}
