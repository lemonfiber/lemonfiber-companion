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
 * Stack files the operator edited, in the stack's order.
 *
 * @implements IteratorAggregate<int, AStackEdit>
 */
final readonly class TheStackEdits implements Countable, IteratorAggregate
{
    /** @param list<AStackEdit> $edits */
    private function __construct(private array $edits) {}

    /** No file the operator edited. */
    public static function none(): self
    {
        return new self([]);
    }

    /** Each file, in order. */
    public static function these(AStackEdit ...$edits): self
    {
        return new self(array_values($edits));
    }

    /** @return Traversable<int, AStackEdit> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->edits);
    }

    public function count(): int
    {
        return count($this->edits);
    }
}
