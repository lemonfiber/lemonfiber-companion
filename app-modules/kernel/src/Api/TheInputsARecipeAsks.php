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
 * Every value a recipe asks the operator for, in the order it declares them.
 *
 * @implements IteratorAggregate<int, AnInputARecipeAsks>
 */
final readonly class TheInputsARecipeAsks implements Countable, IteratorAggregate
{
    /** @param list<AnInputARecipeAsks> $inputs */
    private function __construct(private array $inputs) {}

    /** These inputs, in the manifest's order; reindexed for a named spread's keys. */
    public static function these(AnInputARecipeAsks ...$inputs): self
    {
        return new self(array_values($inputs));
    }

    /** @return Traversable<int, AnInputARecipeAsks> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->inputs);
    }

    public function count(): int
    {
        return count($this->inputs);
    }
}
