<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_filter;
use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Where each service a verb waited for stood when it finished, in the stack's order.
 *
 * @implements IteratorAggregate<int, WhereAServiceEndedUp>
 */
final readonly class WhereTheServicesEndedUp implements Countable, IteratorAggregate
{
    /** @param list<WhereAServiceEndedUp> $services */
    private function __construct(private array $services) {}

    /** In the order given; reindexed for a named spread's keys. */
    public static function of(WhereAServiceEndedUp ...$services): self
    {
        return new self(array_values($services));
    }

    /**
     * The ones that did not come back, in the same order.
     *
     * Which states count as back is {@see HowAServiceRuns::cameBack()}'s
     * decision, asked here rather than answered again.
     */
    public function thatDidNotComeBack(): self
    {
        return new self(array_values(array_filter(
            $this->services,
            static fn(WhereAServiceEndedUp $service): bool => ! $service->runs()->cameBack(),
        )));
    }

    /** @return Traversable<int, WhereAServiceEndedUp> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->services);
    }

    public function count(): int
    {
        return count($this->services);
    }
}
