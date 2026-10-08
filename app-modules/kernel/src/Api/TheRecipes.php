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
 * Every recipe a plugin declares, in the manifest's order.
 *
 * @implements IteratorAggregate<int, ARecipe>
 */
final readonly class TheRecipes implements Countable, IteratorAggregate
{
    /** @param list<ARecipe> $recipes */
    private function __construct(private array $recipes) {}

    /** These, in the stack's order. */
    public static function these(ARecipe ...$recipes): self
    {
        return new self(array_values($recipes));
    }

    /** @return Traversable<int, ARecipe> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->recipes);
    }

    public function count(): int
    {
        return count($this->recipes);
    }
}
