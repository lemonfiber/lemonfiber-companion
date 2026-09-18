<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * Everything running on the machine that this stack did not put there.
 *
 * A typed collection rather than an array (`D1`), and separate from
 * {@see Daemons} rather than a corner of it. These have to be
 * reachable and named and for none of them to be presented as part of the
 * stack; keeping them in a different collection of a different type is what
 * makes the second half true without a screen having to remember it.
 *
 * Empty is the ordinary answer and is not an absence. A machine running only
 * what the stack declares is the expected shape, and a screen that read empty
 * as *the machine did not say* would tell somebody nothing is there when what
 * it means is that nothing is unaccounted for.
 *
 * The order is the machine's own. Nothing here ranks one container above
 * another, because the app has no opinion about software it does not manage,
 * and inventing one would be the first step towards acting on it.
 *
 * @implements IteratorAggregate<int, SomethingElseRunning>
 */
final readonly class WhatElseIsRunning implements IteratorAggregate
{
    /** @param array<int, SomethingElseRunning> $running */
    private function __construct(private array $running) {}

    /**
     * The containers one machine reported, in the order it reported them.
     *
     * Reindexed for the same reason every collection here is: a variadic
     * collected from named arguments carries those names as keys, and
     * everything below reads this by position.
     */
    public static function these(SomethingElseRunning ...$running): self
    {
        return new self(array_values($running));
    }

    /** A machine running nothing but what the stack declares. */
    public static function nothing(): self
    {
        return new self([]);
    }

    public function isEmpty(): bool
    {
        return $this->running === [];
    }

    public function count(): int
    {
        return count($this->running);
    }

    /** @return Traversable<int, SomethingElseRunning> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->running);
    }
}
