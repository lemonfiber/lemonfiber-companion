<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * One kind of what a stack lists, newest first, or word that it could not read that kind.
 *
 * Two answers rather than one list, because an empty list taken for everything
 * there is would have the phone mark all of it as new once the stack could read
 * it again.
 *
 * @template TItem of object
 *
 * @implements IteratorAggregate<int, TItem>
 */
final readonly class WhatTheStackListed implements IteratorAggregate
{
    /** @param list<TItem> $items */
    private function __construct(private array $items, private bool $read) {}

    /**
     * These, newest first.
     *
     * @template TListed of object
     *
     * @param TListed ...$items
     *
     * @return self<TListed>
     */
    public static function these(object ...$items): self
    {
        return new self(array_values($items), read: true);
    }

    /**
     * The stack could not read this kind, so nothing is listed and nothing is to be made of that.
     *
     * @return self<never>
     */
    public static function unread(): self
    {
        return new self([], read: false);
    }

    /** Whether the stack read this kind, so that the list is everything there is. */
    public function wasRead(): bool
    {
        return $this->read;
    }

    /** @return Traversable<int, TItem> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
