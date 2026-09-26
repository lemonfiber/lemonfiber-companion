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
 * What may be done about a setup already here, least destructive first, as the stack ordered them.
 *
 * @implements IteratorAggregate<int, AMode>
 */
final readonly class TheModes implements Countable, IteratorAggregate
{
    /** @param list<AMode> $modes */
    private function __construct(private array $modes) {}

    /** These modes, in the stack's order; reindexed for a named spread's keys. */
    public static function of(AMode ...$modes): self
    {
        return new self(array_values($modes));
    }

    /** @return Traversable<int, AMode> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->modes);
    }

    public function count(): int
    {
        return count($this->modes);
    }
}
