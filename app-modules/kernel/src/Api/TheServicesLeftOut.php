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

/**
 * The services the stack left out of the forms asked for, in the stack's order.
 *
 * @implements IteratorAggregate<int, AServiceLeftOut>
 */
final readonly class TheServicesLeftOut implements Countable, IteratorAggregate
{
    /** @param list<AServiceLeftOut> $services */
    private function __construct(private array $services) {}

    /**
     * In the order given.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(AServiceLeftOut ...$services): self
    {
        return new self(array_values($services));
    }

    /** Whether that service is one of them, which makes it filtered rather than absent. */
    public function include(ServiceId $service): bool
    {
        return array_any($this->services, static fn(AServiceLeftOut $left): bool => $left->id()->isTheSameAs($service));
    }

    /** @return Traversable<int, AServiceLeftOut> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->services);
    }

    public function count(): int
    {
        return count($this->services);
    }
}
