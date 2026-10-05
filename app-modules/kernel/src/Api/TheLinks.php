<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * What a stack wires to what: every link in the order it declares them, and every capability nothing fills.
 *
 * **Empty is an answer.** A stack that asks nothing of its services sends no
 * link, and that comes through here as a value; a wiring that could not be
 * read never does, so the two cannot be confused.
 *
 * @implements IteratorAggregate<int, ALink>
 */
final readonly class TheLinks implements IteratorAggregate
{
    /** @param list<ALink> $links */
    private function __construct(
        private array $links,
        private WhatNothingFills $unfilled,
    ) {}

    /**
     * The links, in the order the stack declares them, and what nothing fills.
     *
     * Reindexed for {@see Services::these()}'s reason.
     */
    public static function of(WhatNothingFills $unfilled, ALink ...$links): self
    {
        return new self(array_values($links), $unfilled);
    }

    /** Every capability something asks for and nothing fills, naming what asked. */
    public function unfilled(): WhatNothingFills
    {
        return $this->unfilled;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->links);
    }
}
