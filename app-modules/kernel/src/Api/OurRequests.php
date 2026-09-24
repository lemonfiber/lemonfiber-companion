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
 * Every request lemonfiber makes on its own account, in the stack's fixed order.
 *
 * A collection of its own rather than a list shared with the services', so
 * that nothing holding one can be handed the other: the two are never merged.
 *
 * @implements IteratorAggregate<int, ARequestOfOurs>
 */
final readonly class OurRequests implements Countable, IteratorAggregate
{
    /** @param list<ARequestOfOurs> $requests */
    private function __construct(private array $requests) {}

    /** These requests, in the stack's order; reindexed for a named spread's keys. */
    public static function of(ARequestOfOurs ...$requests): self
    {
        return new self(array_values($requests));
    }

    /** @return Traversable<int, ARequestOfOurs> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->requests);
    }

    public function count(): int
    {
        return count($this->requests);
    }
}
