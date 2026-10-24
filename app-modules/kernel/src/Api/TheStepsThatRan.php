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
 * Every step a recipe declares, in its order, with what each came to.
 *
 * @implements IteratorAggregate<int, AStepThatRan>
 */
final readonly class TheStepsThatRan implements Countable, IteratorAggregate
{
    /** @param list<AStepThatRan> $steps */
    private function __construct(private array $steps) {}

    /** These steps, in the recipe's order; reindexed for a named spread's keys. */
    public static function these(AStepThatRan ...$steps): self
    {
        return new self(array_values($steps));
    }

    /** @return Traversable<int, AStepThatRan> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->steps);
    }

    public function count(): int
    {
        return count($this->steps);
    }
}
