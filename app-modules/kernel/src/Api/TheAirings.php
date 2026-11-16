<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * One channel's programmes in the guide, in the order the core sent them, each outside the member's limits left out.
 *
 * @implements IteratorAggregate<int, AnAiring>
 */
final readonly class TheAirings implements IteratorAggregate
{
    /** @param list<AnAiring> $airings */
    private function __construct(private array $airings) {}

    public static function of(AnAiring ...$airings): self
    {
        return new self(array_values($airings));
    }

    /** @return Traversable<int, AnAiring> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->airings);
    }
}
