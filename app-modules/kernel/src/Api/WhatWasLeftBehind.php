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
 * Everything a removal could not take, each with how to finish it by hand.
 *
 * @implements IteratorAggregate<int, SomethingLeftBehind>
 */
final readonly class WhatWasLeftBehind implements Countable, IteratorAggregate
{
    /** @param list<SomethingLeftBehind> $left */
    private function __construct(private array $left) {}

    /** These, in the stack's order. */
    public static function of(SomethingLeftBehind ...$left): self
    {
        return new self(array_values($left));
    }

    /** @return Traversable<int, SomethingLeftBehind> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->left);
    }

    public function count(): int
    {
        return count($this->left);
    }
}
