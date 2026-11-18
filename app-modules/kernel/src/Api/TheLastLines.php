<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * The last lines a run wrote, oldest first, every credential in them already withheld by the stack.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class TheLastLines implements IteratorAggregate
{
    /** @param list<string> $lines */
    private function __construct(private array $lines) {}

    /** These lines, oldest first. */
    public static function these(string ...$lines): self
    {
        return new self(array_values($lines));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->lines);
    }
}
