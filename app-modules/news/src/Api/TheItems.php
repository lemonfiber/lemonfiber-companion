<?php

declare(strict_types=1);

namespace Modules\News\Api;

use function array_filter;
use function array_slice;
use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Every item of one kind a stack holds, newest first, as the stack listed them.
 *
 * It answers what is newer than an item, by the order its kind is newer by: an
 * update by its place in this list, and a request or a problem by its own
 * number or onset.
 *
 * @implements IteratorAggregate<int, AnItem>
 */
final readonly class TheItems implements Countable, IteratorAggregate
{
    /** @param list<AnItem> $items */
    private function __construct(private KindOfNews $kind, private array $items) {}

    /** These, of this kind, newest first. */
    public static function of(KindOfNews $kind, AnItem ...$items): self
    {
        foreach ($items as $item) {
            if ($item->kind() !== $kind) {
                throw NotAnItem::ofAnotherKind($kind, $item->kind());
            }
        }

        return new self($kind, array_values($items));
    }

    /** Which kind they are. */
    public function kind(): KindOfNews
    {
        return $this->kind;
    }

    /**
     * Every item newer than the one seen.
     *
     * An update the stack no longer lists leaves only its newest release new:
     * whatever came between is out of the record, and a list of every release
     * would bury the one that matters.
     */
    public function newerThan(AnItem $seen): WhatIsNew
    {
        if ($this->kind !== KindOfNews::Update) {
            return WhatIsNew::these(...array_filter($this->items, static fn(AnItem $item): bool => $item->order() > $seen->order()));
        }

        $place = $this->placeOf($seen);

        return WhatIsNew::these(...array_slice($this->items, 0, $place ?? 1));
    }

    /** Whether one item is newer than another, both of this kind. */
    public function isNewer(AnItem $item, AnItem $than): bool
    {
        if ($this->kind !== KindOfNews::Update) {
            return $item->order() > $than->order();
        }

        $place = $this->placeOf($item);
        $thanPlace = $this->placeOf($than);

        return $place !== null && ($thanPlace === null || $place < $thanPlace);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return Traversable<int, AnItem> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /** Where an item is in the list, newest at nought, or nothing where it is not. */
    private function placeOf(AnItem $sought): ?int
    {
        foreach ($this->items as $place => $item) {
            if ($item->is($sought)) {
                return $place;
            }
        }

        return null;
    }
}
