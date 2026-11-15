<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * What the core named in the way of reading something, in the order it named them.
 *
 * @implements IteratorAggregate<int, AnObstacleNamed>
 */
final readonly class TheObstaclesNamed implements IteratorAggregate
{
    /** @param list<AnObstacleNamed> $named */
    private function __construct(private array $named) {}

    public static function of(AnObstacleNamed ...$named): self
    {
        return new self(array_values($named));
    }

    /** @return Traversable<int, AnObstacleNamed> */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->named);
    }
}
