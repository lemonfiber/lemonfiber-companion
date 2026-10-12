<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The alerts one stream carried in one wake, in the order the stack raised them.
 *
 * @implements IteratorAggregate<int, AnAlert>
 */
final readonly class TheAlerts implements IteratorAggregate
{
    /** @param list<AnAlert> $alerts */
    private function __construct(private array $alerts) {}

    /** These alerts, in order. */
    public static function of(AnAlert ...$alerts): self
    {
        return new self(array_values($alerts));
    }

    /** No alert. */
    public static function none(): self
    {
        return new self([]);
    }

    /** @return Traversable<int, AnAlert> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->alerts);
    }
}
