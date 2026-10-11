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
 * Every integration key a stack lists, in the order they were minted, with its sentence on what a purpose is worth.
 *
 * None at all is an answer, and it is not *could not be read*: that is an
 * {@see Obstacle}, which {@see WhatWasFoundOfTheKeys} keeps apart.
 *
 * @implements IteratorAggregate<int, AKeyForAnApp>
 */
final readonly class TheKeysForApps implements Countable, IteratorAggregate
{
    /** @param list<AKeyForAnApp> $keys */
    private function __construct(private string $purposes, private array $keys) {}

    /** These keys, in the stack's order; reindexed for a named spread's keys. */
    public static function of(string $purposes, AKeyForAnApp ...$keys): self
    {
        return new self($purposes, array_values($keys));
    }

    /** The stack's own sentence on what a purpose in the listing is worth. */
    public function purposes(): string
    {
        return $this->purposes;
    }

    /** @return Traversable<int, AKeyForAnApp> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->keys);
    }

    public function count(): int
    {
        return count($this->keys);
    }
}
