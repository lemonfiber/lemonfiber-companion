<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_filter;
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
 * whoever last wrote a `foreach`. The rule that belongs here is `N2-R16`'s —
 * a withdrawn release is not an update — and putting it on the collection is
 * what stops each screen from remembering it.
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

    /**
     * Only the ones worth offering as an update.
     *
     * `N2-R16`'s refusal, made once. A screen that filtered for itself would be
     * a second place to forget, and forgetting means offering somebody a
     * release that was taken back.
     */
    public function worthOffering(): self
    {
        return new self(array_values(array_filter(
            $this->releases,
            static fn(Release $release): bool => $release->isWorthOffering(),
        )));
    }

    /**
     * Only the ones somebody in the house would notice.
     *
     * The distinction `N2-R16` turns on, so that a screen can lead with the
     * releases that make this a decision rather than a chore.
     */
    public function theHouseholdWouldNotice(): self
    {
        return new self(array_values(array_filter(
            $this->releases,
            static fn(Release $release): bool => $release->theHouseholdWouldNotice(),
        )));
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
