<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Services by the names the stack gives them, in its order.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class TheServicesNamed implements IteratorAggregate
{
    /** @param list<string> $names */
    private function __construct(private array $names) {}

    public static function of(string ...$names): self
    {
        return new self(array_values($names));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->names);
    }
}
