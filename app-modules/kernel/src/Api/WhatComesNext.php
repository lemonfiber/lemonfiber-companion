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
 * Where a finished walkthrough hands the operator on to, in the stack's order.
 *
 * @implements IteratorAggregate<int, WhatToDoNext>
 */
final readonly class WhatComesNext implements Countable, IteratorAggregate
{
    /** @param list<WhatToDoNext> $next */
    private function __construct(private array $next) {}

    public static function of(WhatToDoNext ...$next): self
    {
        return new self(array_values($next));
    }

    /** @return Traversable<int, WhatToDoNext> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->next);
    }

    public function count(): int
    {
        return count($this->next);
    }
}
