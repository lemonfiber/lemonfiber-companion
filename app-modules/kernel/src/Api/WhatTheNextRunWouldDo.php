<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The acts the queue's next run would take on an unhealthy item, each its own, in the order it would take them.
 *
 * @implements IteratorAggregate<int, AHealingAct>
 */
final readonly class WhatTheNextRunWouldDo implements IteratorAggregate
{
    /** @param list<AHealingAct> $acts */
    private function __construct(private array $acts) {}

    /** These acts, in order. */
    public static function these(AHealingAct ...$acts): self
    {
        return new self(array_values($acts));
    }

    /** @return Traversable<int, AHealingAct> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->acts);
    }
}
