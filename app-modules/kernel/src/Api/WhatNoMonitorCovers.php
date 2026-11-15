<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every critical service and household entry point no active monitor covers, in the order the stack sent them.
 *
 * @implements IteratorAggregate<int, WhatAMonitorCovers>
 */
final readonly class WhatNoMonitorCovers implements IteratorAggregate
{
    /** @param list<WhatAMonitorCovers> $things */
    private function __construct(private array $things) {}

    /** These things. */
    public static function these(WhatAMonitorCovers ...$things): self
    {
        return new self(array_values($things));
    }

    /** @return Traversable<int, WhatAMonitorCovers> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->things);
    }
}
