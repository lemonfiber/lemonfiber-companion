<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * One direction on one download client: the verdict, and each rate it gave of what it was asked to hold to, reports in force and moves.
 *
 * @implements IteratorAggregate<int, ARateGiven>
 */
final readonly class ADirectionHeld implements IteratorAggregate
{
    /** @param list<ARateGiven> $rates */
    private function __construct(private WhatALimitCameTo $verdict, private array $rates) {}

    /** The direction as the client answered for it, with the rates it gave. */
    public static function said(WhatALimitCameTo $verdict, ARateGiven ...$rates): self
    {
        return new self($verdict, array_values($rates));
    }

    /** What that adds up to. */
    public function verdict(): WhatALimitCameTo
    {
        return $this->verdict;
    }

    /** @return Traversable<int, ARateGiven> each rate it gave, in the order it gave them */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rates);
    }
}
