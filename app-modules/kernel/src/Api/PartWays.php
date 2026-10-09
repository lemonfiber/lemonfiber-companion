<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * What a member was part-way through, most recent first, as the core answers it.
 *
 * @implements IteratorAggregate<int, APartWay>
 */
final readonly class PartWays implements IteratorAggregate
{
    /** @param list<APartWay> $each */
    private function __construct(private array $each) {}

    public static function of(APartWay ...$each): self
    {
        return new self(array_values($each));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->each);
    }
}
