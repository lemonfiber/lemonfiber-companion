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
 * Everything one declaration covers now, in the stack's order, and how many more it left unlisted.
 *
 * @implements IteratorAggregate<int, AThingLeftAlone>
 */
final readonly class WhatAnAreaCovers implements Countable, IteratorAggregate
{
    use ReadsWhatIsLeftAlone;

    /** @param list<AThingLeftAlone> $things */
    private function __construct(private array $things, private int $more) {}

    public static function these(int $more, AThingLeftAlone ...$things): self
    {
        return new self(array_values($things), self::counted($more, 'covered things'));
    }

    public function more(): int
    {
        return $this->more;
    }

    public function count(): int
    {
        return count($this->things);
    }

    /** @return Traversable<int, AThingLeftAlone> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->things);
    }
}
