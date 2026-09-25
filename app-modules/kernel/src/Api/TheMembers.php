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
 * Everybody the media server holds an account for, in the stack's order.
 *
 * None at all is an answer — a household nobody has been asked into — and is
 * told apart from one that could not be read by {@see WhatWasFoundOfTheMembers}.
 *
 * @implements IteratorAggregate<int, AMember>
 */
final readonly class TheMembers implements Countable, IteratorAggregate
{
    /** @param list<AMember> $members */
    private function __construct(private array $members) {}

    /** These members, in the stack's order; reindexed for a named spread's keys. */
    public static function of(AMember ...$members): self
    {
        return new self(array_values($members));
    }

    /** @return Traversable<int, AMember> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->members);
    }

    public function count(): int
    {
        return count($this->members);
    }
}
