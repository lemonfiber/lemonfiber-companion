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
 * The placeholders a starting file names secrets by, in the order it holds them.
 *
 * @implements IteratorAggregate<int, APlaceholder>
 */
final readonly class ThePlaceholders implements Countable, IteratorAggregate
{
    /** @param list<APlaceholder> $placeholders */
    private function __construct(private array $placeholders) {}

    /** Each placeholder, in order. */
    public static function these(APlaceholder ...$placeholders): self
    {
        return new self(array_values($placeholders));
    }

    /** @return Traversable<int, APlaceholder> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->placeholders);
    }

    public function count(): int
    {
        return count($this->placeholders);
    }
}
