<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * The releases a stack named, in the order it named them.
 *
 * A type rather than an array, which is `D1`: an array has no name, no
 * invariants and nowhere to put the rules, so what is in it ends up living in
 * whoever last wrote a `foreach`.
 *
 * @implements IteratorAggregate<int, Release>
 */
final readonly class Releases implements IteratorAggregate
{
    /** @param list<Release> $releases */
    private function __construct(private array $releases) {}

    /**
     * The releases a stack named.
     *
     * Reindexed rather than taken as it arrives: a variadic collected from
     * named arguments carries their names as keys, so being variadic is not
     * the same claim as being a list.
     */
    public static function these(Release ...$releases): self
    {
        return new self(array_values($releases));
    }

    /** No releases at all, which a stack that has not looked answers with. */
    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->releases);
    }

    public function isEmpty(): bool
    {
        return $this->releases === [];
    }

    /** @return Traversable<int, Release> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->releases);
    }
}
