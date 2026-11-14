<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Override;
use Traversable;

/**
 * What a member can search among, in the core's order.
 *
 * @implements IteratorAggregate<int, WhichTitles>
 */
final readonly class ChoicesOfTitles implements IteratorAggregate
{
    /** @param list<WhichTitles> $choices */
    private function __construct(private array $choices) {}

    public static function of(WhichTitles ...$choices): self
    {
        return new self(array_values($choices));
    }

    public function count(): int
    {
        return count($this->choices);
    }

    /** The first of them, or films and series where there is none. */
    public function first(): WhichTitles
    {
        return $this->choices === [] ? WhichTitles::filmsAndSeries() : $this->choices[0];
    }

    /** @return Traversable<int, WhichTitles> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->choices);
    }
}
