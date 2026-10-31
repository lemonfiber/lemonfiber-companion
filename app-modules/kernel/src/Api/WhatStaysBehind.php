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
 * Every kind of thing a substitution would leave behind with the part it stops, in the order the stack listed them.
 *
 * None at all is an answer: everything the part held is carried, or it held
 * nothing lemonfiber does not seed.
 *
 * @implements IteratorAggregate<int, ALeftBehind>
 */
final readonly class WhatStaysBehind implements Countable, IteratorAggregate
{
    /** @param list<ALeftBehind> $behind */
    private function __construct(private array $behind) {}

    /** These, in the stack's order; reindexed for a named spread's keys. */
    public static function these(ALeftBehind ...$behind): self
    {
        return new self(array_values($behind));
    }

    /** Nothing stays behind. */
    public static function none(): self
    {
        return new self([]);
    }

    /** @return Traversable<int, ALeftBehind> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->behind);
    }

    public function count(): int
    {
        return count($this->behind);
    }
}
