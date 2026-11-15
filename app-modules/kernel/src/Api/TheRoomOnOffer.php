<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * The room a stack offers back as costing nothing, by category, under the name the offer goes by.
 *
 * @implements IteratorAggregate<int, ACategoryOffered>
 */
final readonly class TheRoomOnOffer implements Countable, IteratorAggregate
{
    /** @param list<ACategoryOffered> $categories */
    private function __construct(private string $agreement, private array $categories) {}

    /** The offer by its name, its categories in the stack's order. */
    public static function named(string $agreement, ACategoryOffered ...$categories): self
    {
        if (trim($agreement) === '') {
            throw RoomSaysNothing::about('agreement');
        }

        return new self($agreement, array_values($categories));
    }

    /** The name the offer goes by, which a yes quotes. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** Whether the offer holds a category about this. */
    public function holds(WhatALineIsAbout $about): bool
    {
        return array_any($this->categories, fn(ACategoryOffered $category): bool => $category->about() === $about);
    }

    /** @return Traversable<int, ACategoryOffered> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->categories);
    }

    public function count(): int
    {
        return count($this->categories);
    }
}
