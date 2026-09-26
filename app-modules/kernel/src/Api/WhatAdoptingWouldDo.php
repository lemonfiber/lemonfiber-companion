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
 * What adopting each recognised service would come to, in the stack's order.
 *
 * @implements IteratorAggregate<int, WhatAdoptingOneWouldDo>
 */
final readonly class WhatAdoptingWouldDo implements Countable, IteratorAggregate
{
    /** @param list<WhatAdoptingOneWouldDo> $services */
    private function __construct(private array $services) {}

    /** These services, in the stack's order; reindexed for a named spread's keys. */
    public static function of(WhatAdoptingOneWouldDo ...$services): self
    {
        return new self(array_values($services));
    }

    /** @return Traversable<int, WhatAdoptingOneWouldDo> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->services);
    }

    public function count(): int
    {
        return count($this->services);
    }
}
