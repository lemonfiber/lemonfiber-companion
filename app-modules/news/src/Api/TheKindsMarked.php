<?php

declare(strict_types=1);

namespace Modules\News\Api;

use function array_values;

use ArrayIterator;

use function in_array;

use IteratorAggregate;
use Traversable;

/**
 * The kinds a stack marks as new, in the order the kinds are declared.
 *
 * @implements IteratorAggregate<int, KindOfNews>
 */
final readonly class TheKindsMarked implements IteratorAggregate
{
    /** @param list<KindOfNews> $kinds */
    private function __construct(private array $kinds) {}

    /** These kinds. */
    public static function these(KindOfNews ...$kinds): self
    {
        return new self(array_values($kinds));
    }

    /** Whether the kind is among them. */
    public function include(KindOfNews $kind): bool
    {
        return in_array($kind, $this->kinds, strict: true);
    }

    /** @return Traversable<int, KindOfNews> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->kinds);
    }
}
