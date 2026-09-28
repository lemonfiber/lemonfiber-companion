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
 * Every line a removal reaches, in the stack's order, going and kept alike.
 *
 * @implements IteratorAggregate<int, OneThingItReaches>
 */
final readonly class WhatItReaches implements Countable, IteratorAggregate
{
    /** @param list<OneThingItReaches> $lines */
    private function __construct(private array $lines) {}

    /** These, in the stack's order. */
    public static function of(OneThingItReaches ...$lines): self
    {
        return new self(array_values($lines));
    }

    /** @return Traversable<int, OneThingItReaches> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->lines);
    }

    public function count(): int
    {
        return count($this->lines);
    }
}
