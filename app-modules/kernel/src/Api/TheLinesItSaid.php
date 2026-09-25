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
 * Every line a walkthrough said, in the order it said them.
 *
 * Held in the order given and handed out in that order, never sorted or
 * grouped: an operator who looked away and looks back is shown the same story.
 *
 * @implements IteratorAggregate<int, ALineItSaid>
 */
final readonly class TheLinesItSaid implements Countable, IteratorAggregate
{
    /** @param list<ALineItSaid> $lines */
    private function __construct(private array $lines) {}

    public static function of(ALineItSaid ...$lines): self
    {
        return new self(array_values($lines));
    }

    /** @return Traversable<int, ALineItSaid> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->lines);
    }

    public function count(): int
    {
        return count($this->lines);
    }
}
