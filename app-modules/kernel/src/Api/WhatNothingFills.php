<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * The capabilities nothing answers, each with the service still asking.
 *
 * A type rather than an array, which is `D1`. {@see Services} is the same shape
 * one noun over, and the order is the core's: nothing is sorted here, because
 * any order this imposed would be an opinion about which unfilled capability
 * matters most, and the core did not send one.
 *
 * **Empty is an answer.** A stack where everything is answered says so by
 * sending an empty list, and {@see self::none()} is how that is held — the
 * argument {@see Services::none()} makes, and it matters more here: an empty
 * list and a list that could not be read are the two things a screen must not
 * confuse, and a type that could only be absent would make them identical.
 *
 * @implements IteratorAggregate<int, Unfilled>
 */
final readonly class WhatNothingFills implements IteratorAggregate
{
    /** @param list<Unfilled> $unfilled */
    private function __construct(private array $unfilled) {}

    /**
     * The capabilities nothing fills, in the order the core listed them.
     *
     * Reindexed rather than taken as it arrives, for {@see Services::these()}'s
     * reason: a variadic collected from named arguments carries their names as
     * keys, so being variadic is not the same claim as being a list.
     */
    public static function these(Unfilled ...$unfilled): self
    {
        return new self(array_values($unfilled));
    }

    /** Everything is answered, which is a fact rather than a gap. */
    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->unfilled);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->unfilled);
    }
}
