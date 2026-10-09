<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * One season's episodes, in the order the core lists them.
 *
 * @implements IteratorAggregate<int, AnEpisode>
 */
final readonly class Episodes implements IteratorAggregate
{
    /** @param list<AnEpisode> $each */
    private function __construct(private array $each) {}

    public static function of(AnEpisode ...$each): self
    {
        return new self(array_values($each));
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->each);
    }
}
