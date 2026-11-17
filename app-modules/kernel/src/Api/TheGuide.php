<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every channel's guide the core answers one member, in the order it sent them.
 *
 * None is an answer: the house has no live TV, or this member's account may not watch it.
 *
 * @implements IteratorAggregate<int, AChannelsGuide>
 */
final readonly class TheGuide implements IteratorAggregate
{
    /** @param list<AChannelsGuide> $channels */
    private function __construct(private array $channels) {}

    public static function of(AChannelsGuide ...$channels): self
    {
        return new self(array_values($channels));
    }

    public function isEmpty(): bool
    {
        return $this->channels === [];
    }

    /** @return Traversable<int, AChannelsGuide> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->channels);
    }
}
