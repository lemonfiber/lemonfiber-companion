<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every live TV channel the core says one member may watch, in the order it sent them.
 *
 * None is an answer: the house has no live TV, or this member's account may not watch it.
 *
 * @implements IteratorAggregate<int, AChannel>
 */
final readonly class TheChannels implements IteratorAggregate
{
    /** @param list<AChannel> $channels */
    private function __construct(private array $channels) {}

    public static function these(AChannel ...$channels): self
    {
        return new self(array_values($channels));
    }

    public function isEmpty(): bool
    {
        return $this->channels === [];
    }

    /** @return Traversable<int, AChannel> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->channels);
    }
}
