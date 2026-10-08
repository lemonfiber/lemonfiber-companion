<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every call one recipe makes, in the order it makes them.
 *
 * @implements IteratorAggregate<int, ARecipeStep>
 */
final readonly class TheRecipeSteps implements IteratorAggregate
{
    /** @param list<ARecipeStep> $steps */
    private function __construct(private array $steps) {}

    /** These, in the stack's order. */
    public static function these(ARecipeStep ...$steps): self
    {
        return new self(array_values($steps));
    }

    /** @return Traversable<int, ARecipeStep> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->steps);
    }
}
