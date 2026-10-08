<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Every value one recipe could carry, and where to.
 *
 * @implements IteratorAggregate<int, AValueItCarries>
 */
final readonly class TheValuesItCarries implements IteratorAggregate
{
    /** @param list<AValueItCarries> $pairs */
    private function __construct(private array $pairs) {}

    /** These, in the stack's order. */
    public static function these(AValueItCarries ...$pairs): self
    {
        return new self(array_values($pairs));
    }

    /** @return Traversable<int, AValueItCarries> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->pairs);
    }
}
