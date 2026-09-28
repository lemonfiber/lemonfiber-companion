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
 * The files putting the configuration back reverts, in the stack's order.
 *
 * @implements IteratorAggregate<int, AnEditReverted>
 */
final readonly class EditsReverted implements Countable, IteratorAggregate
{
    /** @param list<AnEditReverted> $edits */
    private function __construct(private array $edits) {}

    /** Each file, in order. */
    public static function these(AnEditReverted ...$edits): self
    {
        return new self(array_values($edits));
    }

    /** @return Traversable<int, AnEditReverted> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->edits);
    }

    public function count(): int
    {
        return count($this->edits);
    }
}
