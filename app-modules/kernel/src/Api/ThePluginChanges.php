<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every change an install makes to the machine, in the order it makes them.
 *
 * @implements IteratorAggregate<int, APluginChange>
 */
final readonly class ThePluginChanges implements IteratorAggregate
{
    /** @param list<APluginChange> $changes */
    private function __construct(private array $changes) {}

    /** These, in the stack's order. */
    public static function these(APluginChange ...$changes): self
    {
        return new self(array_values($changes));
    }

    /** @return Traversable<int, APluginChange> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->changes);
    }
}
