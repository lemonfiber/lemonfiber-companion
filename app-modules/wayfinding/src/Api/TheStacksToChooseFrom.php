<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * The rows of the list of stacks, in the order this phone keeps them.
 *
 * @implements IteratorAggregate<int, AStackToChooseAsShown>
 */
final readonly class TheStacksToChooseFrom implements Countable, IteratorAggregate
{
    /** @param list<AStackToChooseAsShown> $rows */
    private function __construct(private array $rows) {}

    public static function of(AStackToChooseAsShown ...$rows): self
    {
        return new self(array_values($rows));
    }

    /** The list while it is shut, which reads nothing. */
    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->rows);
    }

    /** @return Traversable<int, AStackToChooseAsShown> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rows);
    }
}
