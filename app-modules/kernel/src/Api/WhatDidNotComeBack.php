<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * The commands a machine was told to keep running and is not running.
 *
 * A type of its own rather than a filtered array, which is `D1`: an array has
 * nowhere to put the rule that decides what belongs in it, so the knowledge
 * ends up in whoever last wrote the loop — and two screens looping differently
 * is how an operator learns that one of them is lying.
 *
 * **It is the subset a screen draws under a heading**, which is what makes it
 * worth naming. *Everything this machine hosts* and *everything that did not
 * start* are different lists for different moments: the first is what somebody
 * reads when deciding what should survive a reboot, and the second is what they
 * read the morning after one.
 *
 * Empty is the answer wanted most — everything came back — and it says so
 * without a null anywhere (`C2`), the way {@see Stalled} says it.
 *
 * @implements IteratorAggregate<int, Unattended>
 */
final readonly class WhatDidNotComeBack implements IteratorAggregate
{
    /** @param array<int, Unattended> $commands */
    private function __construct(private array $commands) {}

    /**
     * The ones a listing found, in the order the stack gave them.
     *
     * Not habit on the reindex: a variadic collected from named arguments has
     * string keys, and everything reading this reads it by position.
     */
    public static function these(Unattended ...$commands): self
    {
        return new self(array_values($commands));
    }

    public function count(): int
    {
        return count($this->commands);
    }

    /** @return Traversable<int, Unattended> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->commands);
    }
}
