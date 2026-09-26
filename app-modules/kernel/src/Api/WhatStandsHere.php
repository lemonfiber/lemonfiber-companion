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
 * Every project already on a machine that is not lemonfiber's, in the stack's order.
 *
 * @implements IteratorAggregate<int, AProjectStanding>
 */
final readonly class WhatStandsHere implements Countable, IteratorAggregate
{
    /** @param list<AProjectStanding> $projects */
    private function __construct(private array $projects) {}

    /** These projects, in the stack's order; reindexed for a named spread's keys. */
    public static function of(AProjectStanding ...$projects): self
    {
        return new self(array_values($projects));
    }

    /** @return Traversable<int, AProjectStanding> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->projects);
    }

    public function count(): int
    {
        return count($this->projects);
    }
}
