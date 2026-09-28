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
 * What every service a stack declares is for, in the order it declares them.
 *
 * Every service the manifest holds rather than the ones running: what a
 * service is for is asked about the one somebody is deciding whether to run.
 *
 * @implements IteratorAggregate<int, WhatAServiceIsFor>
 */
final readonly class WhatTheServicesAreFor implements Countable, IteratorAggregate
{
    /** @param list<WhatAServiceIsFor> $services */
    private function __construct(private array $services) {}

    /** Each service, in the stack's order. Reindexed for {@see TheRecord::reaching()}'s reason. */
    public static function these(WhatAServiceIsFor ...$services): self
    {
        return new self(array_values($services));
    }

    /** @return Traversable<int, WhatAServiceIsFor> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->services);
    }

    public function count(): int
    {
        return count($this->services);
    }
}
