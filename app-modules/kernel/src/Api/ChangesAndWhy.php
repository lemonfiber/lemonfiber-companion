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
 * Changes a report says something further about, each with what it says, in the stack's order.
 *
 * Empty is an answer: nothing was left standing, or nothing needed noting.
 *
 * @implements IteratorAggregate<int, AChangeAndWhy>
 */
final readonly class ChangesAndWhy implements Countable, IteratorAggregate
{
    /** @param list<AChangeAndWhy> $changes */
    private function __construct(private array $changes) {}

    /** Each change, in the stack's order. Reindexed for {@see TheRecord::reaching()}'s reason. */
    public static function these(AChangeAndWhy ...$changes): self
    {
        return new self(array_values($changes));
    }

    /** @return Traversable<int, AChangeAndWhy> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->changes);
    }

    public function count(): int
    {
        return count($this->changes);
    }
}
