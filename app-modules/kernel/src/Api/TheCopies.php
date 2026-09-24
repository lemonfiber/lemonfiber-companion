<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * The copies of the stack this machine holds, by the names they were written
 * under.
 *
 * Names, never paths: a restore is asked for by name, and a name is the one
 * thing an operator with no filesystem in front of them can use. An empty list
 * is an answer — no copy has been taken — and is a different answer from a
 * list that could not be read, which is {@see WhatCopiesWereFound}'s to keep
 * apart.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class TheCopies implements Countable, IteratorAggregate
{
    /** @param list<string> $names */
    private function __construct(private array $names) {}

    /**
     * The copies, in the stack's order, each name required.
     *
     * A blank name is refused rather than listed: a copy nobody can name is
     * one nobody could ever ask to have put back.
     */
    public static function named(string ...$names): self
    {
        foreach ($names as $name) {
            if (trim($name) === '') {
                throw KeepingSaysNothing::about('archive');
            }
        }

        return new self(array_values($names));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->names);
    }

    public function count(): int
    {
        return count($this->names);
    }
}
