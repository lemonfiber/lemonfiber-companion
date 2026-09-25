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
 * What a copy holds, one line for each thing in it, in the stack's order.
 *
 * The labels an archive lists before anything is overwritten, and the host
 * paths a copy of an existing setup was read from. Words the stack wrote for
 * an operator, carried as it wrote them.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhatACopyHolds implements Countable, IteratorAggregate
{
    /** @param list<string> $held */
    private function __construct(private array $held) {}

    /**
     * Each thing, in order, none of them blank.
     *
     * A line that says nothing is refused rather than listed: a copy that
     * will not say what one of its parts is reads as complete to somebody
     * deciding whether to put it back.
     */
    public static function these(string ...$held): self
    {
        foreach ($held as $one) {
            if (trim($one) === '') {
                throw KeepingSaysNothing::about('label');
            }
        }

        return new self(array_values($held));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->held);
    }

    public function count(): int
    {
        return count($this->held);
    }
}
