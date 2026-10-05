<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * Every service that claims one capability, each with where it came from, in the core's order.
 *
 * Nothing is sorted here, for {@see WhatSettledIt::contested()}'s reason: any
 * order this imposed would be an opinion about which claimant ought to win.
 * Empty where nothing claims it.
 *
 * @implements IteratorAggregate<int, AClaimant>
 */
final readonly class TheClaimants implements IteratorAggregate
{
    /** @param list<AClaimant> $claimants */
    private function __construct(private array $claimants) {}

    /**
     * The claimants, in the order the core listed them.
     *
     * Reindexed for {@see Services::these()}'s reason.
     */
    public static function these(AClaimant ...$claimants): self
    {
        return new self(array_values($claimants));
    }

    /** Nothing claims it. */
    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->claimants);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->claimants);
    }
}
