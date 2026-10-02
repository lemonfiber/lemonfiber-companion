<?php

declare(strict_types=1);

namespace Modules\News\Internal;

use function array_filter;
use function array_key_exists;
use function array_values;
use function in_array;

use Modules\News\Api\AnItem;
use Modules\News\Api\KindOfNews;

/**
 * Everything the phone keeps about what is new on one stack: the kinds switched off, and the newest of each kind seen.
 *
 * The kinds switched off rather than on, so a stack nothing was ever chosen for
 * marks all three. Kept whole, sealed as one, so a store holds one row a stack
 * and nothing in it can be read without the seal's key.
 */
final readonly class WhatIsKeptOfNews
{
    /**
     * @param list<KindOfNews>          $off  the kinds not marked as new
     * @param array<string, AnItem>     $seen the newest item seen of each kind, by the kind's value
     */
    private function __construct(private array $off, private array $seen) {}

    /** Nothing kept: every kind marked, and nothing seen. */
    public static function nothing(): self
    {
        return new self([], []);
    }

    /**
     * What was kept, as read back.
     *
     * @param list<KindOfNews>      $off
     * @param array<string, AnItem> $seen
     */
    public static function of(array $off, array $seen): self
    {
        return new self($off, $seen);
    }

    /** Whether the kind is marked as new. */
    public function isMarked(KindOfNews $kind): bool
    {
        return ! in_array($kind, $this->off, strict: true);
    }

    /** Whether the newest of the kind was seen. */
    public function hasSeen(KindOfNews $kind): bool
    {
        return array_key_exists($kind->value, $this->seen);
    }

    /** The newest item of the kind seen; ask {@see hasSeen()} first. */
    public function seen(KindOfNews $kind): AnItem
    {
        return $this->seen[$kind->value];
    }

    /** The same, with this item the newest of its kind seen. */
    public function seeing(AnItem $item): self
    {
        return new self($this->off, [...$this->seen, $item->kind()->value => $item]);
    }

    /** The same, with the kind marked as new. */
    public function marking(KindOfNews $kind): self
    {
        return new self(array_values(array_filter($this->off, static fn(KindOfNews $off): bool => $off !== $kind)), $this->seen);
    }

    /** The same, with the kind no longer marked, and the newest of it seen forgotten. */
    public function notMarking(KindOfNews $kind): self
    {
        $seen = array_filter($this->seen, static fn(string $named): bool => $named !== $kind->value, ARRAY_FILTER_USE_KEY);

        return new self([...$this->marking($kind)->off, $kind], $seen);
    }

    /** @return list<KindOfNews> */
    public function switchedOff(): array
    {
        return $this->off;
    }

    /** @return array<string, AnItem> */
    public function everySeen(): array
    {
        return $this->seen;
    }
}
