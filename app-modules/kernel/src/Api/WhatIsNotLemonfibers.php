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
 * Everything beneath the data location that the stack did not put there.
 *
 * @implements IteratorAggregate<int, SomethingNotLemonfibers>
 */
final readonly class WhatIsNotLemonfibers implements Countable, IteratorAggregate
{
    /** @param list<SomethingNotLemonfibers> $found */
    private function __construct(private array $found) {}

    /** These, in the stack's order. */
    public static function of(SomethingNotLemonfibers ...$found): self
    {
        return new self(array_values($found));
    }

    /** @return Traversable<int, SomethingNotLemonfibers> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->found);
    }

    public function count(): int
    {
        return count($this->found);
    }
}
