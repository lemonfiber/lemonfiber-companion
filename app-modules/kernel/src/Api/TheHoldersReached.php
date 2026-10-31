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
 * Every service that holds a credential being replaced, and how far the replacement reached each, in the stack's order.
 *
 * None at all is an answer: a reading reaches nobody yet.
 *
 * @implements IteratorAggregate<int, AHolderReached>
 */
final readonly class TheHoldersReached implements Countable, IteratorAggregate
{
    /** @param list<AHolderReached> $holders */
    private function __construct(private array $holders) {}

    /** These holders, in the stack's order; reindexed for a named spread's keys. */
    public static function of(AHolderReached ...$holders): self
    {
        return new self(array_values($holders));
    }

    /** @return Traversable<int, AHolderReached> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->holders);
    }

    public function count(): int
    {
        return count($this->holders);
    }
}
