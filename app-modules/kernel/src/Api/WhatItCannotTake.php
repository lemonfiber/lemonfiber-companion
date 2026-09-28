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
 * Everything lemonfiber will not remove, each with how to remove it by hand.
 *
 * @implements IteratorAggregate<int, SomethingItCannotTake>
 */
final readonly class WhatItCannotTake implements Countable, IteratorAggregate
{
    /** @param list<SomethingItCannotTake> $outside */
    private function __construct(private array $outside) {}

    /** These, in the stack's order. */
    public static function of(SomethingItCannotTake ...$outside): self
    {
        return new self(array_values($outside));
    }

    /** @return Traversable<int, SomethingItCannotTake> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->outside);
    }

    public function count(): int
    {
        return count($this->outside);
    }
}
