<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_filter;
use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What became of every service an applied update touched.
 *
 * A type rather than an array, which is `D1`, and the place the counting
 * lives. The requirement refuses a single *failed*, and the pressure to flatten
 * comes from the summary line rather than from the rows: a screen needs to open
 * with one sentence, and the cheapest one to write is *it failed*. Answering
 * *did everything arrive* here means the rows keep all four endings and the
 * headline is a separate question rather than a lossy version of them.
 *
 * @implements IteratorAggregate<int, HowAServiceTookIt>
 */
final readonly class HowServicesTookIt implements IteratorAggregate
{
    /** @param list<HowAServiceTookIt> $services */
    private function __construct(private array $services) {}

    /**
     * What the stack reported, in the order it reported it.
     *
     * Reindexed rather than taken as it arrives: a variadic collected from
     * named arguments carries their names as keys, so being variadic is not the
     * same claim as being a list.
     */
    public static function these(HowAServiceTookIt ...$services): self
    {
        return new self(array_values($services));
    }

    /** Nothing has been applied, which is every stack that has not taken one. */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * Only the services that are not where the operator wanted them.
     *
     * The rows worth leading with. Which of the three ways each failed stays on
     * the row, because that is the whole of it — this narrows the list
     * without flattening what is in it.
     */
    public function thatDidNotArrive(): self
    {
        return new self(array_values(array_filter(
            $this->services,
            static fn(HowAServiceTookIt $service): bool => ! $service->ending()->arrived(),
        )));
    }

    /**
     * Whether any service is in a state the stack cannot vouch for.
     *
     * Apart from the question above because it is a different sentence to say:
     * *some did not come back* is something to fix, and *the stack does not
     * know what some are doing* is something to go and look at.
     */
    public function anythingUnanswered(): bool
    {
        return array_any($this->services, fn(HowAServiceTookIt $service): bool => $service->ending()->leftUnanswered());
    }

    public function count(): int
    {
        return count($this->services);
    }

    public function isEmpty(): bool
    {
        return $this->services === [];
    }

    /** @return Traversable<int, HowAServiceTookIt> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->services);
    }
}
