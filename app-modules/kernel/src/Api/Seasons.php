<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * A series' seasons, in the order the core lists them; none for anything else.
 *
 * @implements IteratorAggregate<int, ASeason>
 */
final readonly class Seasons implements IteratorAggregate
{
    /** @param list<ASeason> $each */
    private function __construct(private array $each) {}

    public static function of(ASeason ...$each): self
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
