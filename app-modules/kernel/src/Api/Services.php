<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * Services named together, in the order they were named.
 *
 * A type rather than an array, which is `D1`. {@see Forms} is the same shape
 * one noun over, and for the same reason: what is in a list of names has to
 * live somewhere other than in whoever last wrote a `foreach`.
 *
 * @implements IteratorAggregate<int, ServiceId>
 */
final readonly class Services implements IteratorAggregate
{
    /** @param list<ServiceId> $services */
    private function __construct(private array $services) {}

    /**
     * The services named.
     *
     * Reindexed rather than taken as it arrives: a variadic collected from
     * named arguments carries their names as keys, so being variadic is not
     * the same claim as being a list.
     */
    public static function these(ServiceId ...$services): self
    {
        return new self(array_values($services));
    }

    /** None named, which is a decision somebody wrote rather than a gap. */
    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->services);
    }

    public function isEmpty(): bool
    {
        return $this->services === [];
    }

    /** @return Traversable<int, ServiceId> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->services);
    }
}
