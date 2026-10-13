<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every phone key a stack lists, in the order they were registered.
 *
 * None at all is an answer, and it is not *could not be read*: that is an
 * {@see Obstacle}, which {@see WhatWasFoundOfThePhoneKeys} keeps apart.
 *
 * @implements IteratorAggregate<int, APhoneKey>
 */
final readonly class ThePhoneKeys implements IteratorAggregate
{
    /** @param list<APhoneKey> $keys */
    private function __construct(private array $keys) {}

    /** These keys, in the stack's order; reindexed for a named spread's keys. */
    public static function of(APhoneKey ...$keys): self
    {
        return new self(array_values($keys));
    }

    /** @return Traversable<int, APhoneKey> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->keys);
    }
}
