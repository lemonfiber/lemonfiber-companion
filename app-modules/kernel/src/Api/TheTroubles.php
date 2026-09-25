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
 * What to do when it does not work, one symptom at a time, in the stack's order.
 *
 * @implements IteratorAggregate<int, SomethingThatGoesWrong>
 */
final readonly class TheTroubles implements Countable, IteratorAggregate
{
    /** @param list<SomethingThatGoesWrong> $troubles */
    private function __construct(private array $troubles) {}

    /** These symptoms, in the stack's order; reindexed for a named spread's keys. */
    public static function of(SomethingThatGoesWrong ...$troubles): self
    {
        return new self(array_values($troubles));
    }

    /** @return Traversable<int, SomethingThatGoesWrong> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->troubles);
    }

    public function count(): int
    {
        return count($this->troubles);
    }
}
