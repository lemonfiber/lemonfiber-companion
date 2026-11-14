<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * The ways a member chooses to be told on, by the ids the operator's offer lists them under, and none other.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WaysChosen implements IteratorAggregate
{
    /** @param list<string> $ids */
    private function __construct(private array $ids) {}

    public static function byId(string ...$ids): self
    {
        return new self(array_values($ids));
    }

    /** @return Traversable<int, string> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->ids);
    }
}
