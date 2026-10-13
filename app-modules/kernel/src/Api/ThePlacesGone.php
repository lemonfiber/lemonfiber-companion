<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * Each place lemonfiber kept its files that removing everything took, in the stack's order.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class ThePlacesGone implements IteratorAggregate
{
    /** @param list<string> $places */
    private function __construct(private array $places) {}

    public static function of(string ...$places): self
    {
        return new self(array_values($places));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->places);
    }
}
