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
 * The profiles a start would leave out, in the stack's order.
 *
 * @implements IteratorAggregate<int, AProfileLeftOut>
 */
final readonly class TheProfilesLeftOut implements Countable, IteratorAggregate
{
    /** @param list<AProfileLeftOut> $profiles */
    private function __construct(private array $profiles) {}

    /**
     * In the order given.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(AProfileLeftOut ...$profiles): self
    {
        return new self(array_values($profiles));
    }

    /** @return Traversable<int, AProfileLeftOut> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->profiles);
    }

    public function count(): int
    {
        return count($this->profiles);
    }
}
