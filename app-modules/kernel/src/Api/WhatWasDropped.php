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
 * Every service a stack has dropped, in the order it records them.
 *
 * Empty for a stack that has never dropped anything, which is an answer.
 *
 * @implements IteratorAggregate<int, AServiceDropped>
 */
final readonly class WhatWasDropped implements Countable, IteratorAggregate
{
    /** @param list<AServiceDropped> $dropped */
    private function __construct(private array $dropped) {}

    /** Each service, in the stack's order. Reindexed for {@see TheRecord::reaching()}'s reason. */
    public static function these(AServiceDropped ...$dropped): self
    {
        return new self(array_values($dropped));
    }

    /** @return Traversable<int, AServiceDropped> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->dropped);
    }

    public function count(): int
    {
        return count($this->dropped);
    }
}
