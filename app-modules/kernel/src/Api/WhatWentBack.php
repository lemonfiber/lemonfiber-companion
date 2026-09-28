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
 * Every change putting a run back reversed, in the order the stack reversed them.
 *
 * Newest first, which is the order a reversal has to take; kept rather than
 * re-sorted, because the order is part of what happened.
 *
 * @implements IteratorAggregate<int, AChangePutBack>
 */
final readonly class WhatWentBack implements Countable, IteratorAggregate
{
    /** @param list<AChangePutBack> $changes */
    private function __construct(private array $changes) {}

    /** Each change, in the stack's order. Reindexed for {@see TheRecord::reaching()}'s reason. */
    public static function these(AChangePutBack ...$changes): self
    {
        return new self(array_values($changes));
    }

    /** @return Traversable<int, AChangePutBack> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->changes);
    }

    public function count(): int
    {
        return count($this->changes);
    }
}
