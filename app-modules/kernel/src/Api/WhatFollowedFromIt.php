<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What else is wrong because of one affected item, in the words an operator reads.
 *
 * The core counts these with the item that caused them rather than again, so
 * a full disk and the nine imports it stopped are one thing needing attention.
 * They are carried so the item can say what it took down with it.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhatFollowedFromIt implements IteratorAggregate
{
    /** @param list<string> $said */
    private function __construct(private array $said) {}

    public static function of(string ...$said): self
    {
        return new self(array_values($said));
    }

    public function count(): int
    {
        return count($this->said);
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->said);
    }
}
