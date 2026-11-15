<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every curator or download client the queue's latest pass could not reach or verify, in the order the stack sent them.
 *
 * @implements IteratorAggregate<int, AServiceHealingCouldNotReach>
 */
final readonly class WhatHealingCouldNotReach implements IteratorAggregate
{
    /** @param list<AServiceHealingCouldNotReach> $services */
    private function __construct(private array $services) {}

    /** These services. */
    public static function these(AServiceHealingCouldNotReach ...$services): self
    {
        return new self(array_values($services));
    }

    /** @return Traversable<int, AServiceHealingCouldNotReach> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->services);
    }
}
