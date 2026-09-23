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
 * What each of the stack's services reaches, in the stack's order.
 *
 * {@see OurRequests}' counterpart, kept a separate type for the same reason:
 * nothing holding one can be handed the other.
 *
 * @implements IteratorAggregate<int, ARequestOfTheirs>
 */
final readonly class TheirRequests implements Countable, IteratorAggregate
{
    /** @param list<ARequestOfTheirs> $requests */
    private function __construct(private array $requests) {}

    /** These requests, in the stack's order; reindexed for a named spread's keys. */
    public static function of(ARequestOfTheirs ...$requests): self
    {
        return new self(array_values($requests));
    }

    /** @return Traversable<int, ARequestOfTheirs> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->requests);
    }

    public function count(): int
    {
        return count($this->requests);
    }
}
