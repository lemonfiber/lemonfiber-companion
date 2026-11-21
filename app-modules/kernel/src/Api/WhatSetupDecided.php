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
 * Every decision setup makes, in the stack's order, beside the settings the same read listed.
 *
 * @implements IteratorAggregate<int, ADecision>
 */
final readonly class WhatSetupDecided implements Countable, IteratorAggregate
{
    /** @param list<ADecision> $decisions */
    private function __construct(private Settings $set, private array $decisions) {}

    public static function beside(Settings $set, ADecision ...$decisions): self
    {
        return new self($set, array_values($decisions));
    }

    public function settings(): Settings
    {
        return $this->set;
    }

    public function count(): int
    {
        return count($this->decisions);
    }

    /** @return Traversable<int, ADecision> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->decisions);
    }
}
