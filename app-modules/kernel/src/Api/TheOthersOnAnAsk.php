<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The other members one ask the core keeps is also for, by name, in the order the core lists them.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class TheOthersOnAnAsk implements IteratorAggregate
{
    /** @param list<string> $names */
    private function __construct(private array $names) {}

    /** Nobody else: one member asked. */
    public static function none(): self
    {
        return new self([]);
    }

    /** These members, by name. */
    public static function these(string ...$names): self
    {
        return new self(array_values($names));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->names);
    }
}
