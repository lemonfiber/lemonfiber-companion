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
 * Every request lemonfiber makes on an installed plugin's behalf, in the stack's order.
 *
 * Its own type beside {@see OurRequests} and {@see TheirRequests}, so nothing
 * holding one of the three lists can be handed another.
 *
 * @implements IteratorAggregate<int, ARequestOnAPluginsBehalf>
 */
final readonly class RequestsOnAPluginsBehalf implements Countable, IteratorAggregate
{
    /** @param list<ARequestOnAPluginsBehalf> $requests */
    private function __construct(private array $requests) {}

    /** These requests, in the stack's order; reindexed for a named spread's keys. */
    public static function of(ARequestOnAPluginsBehalf ...$requests): self
    {
        return new self(array_values($requests));
    }

    /** @return Traversable<int, ARequestOnAPluginsBehalf> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->requests);
    }

    public function count(): int
    {
        return count($this->requests);
    }
}
