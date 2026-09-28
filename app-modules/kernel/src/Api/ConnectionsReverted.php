<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * The service connections putting the configuration back returns to lemonfiber's.
 *
 * Each is named as the stack names it in a seed report, and carried as it
 * wrote it.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class ConnectionsReverted implements Countable, IteratorAggregate
{
    /** @param list<string> $named */
    private function __construct(private array $named) {}

    /** Each connection, in order, none of them blank. */
    public static function these(string ...$named): self
    {
        foreach ($named as $one) {
            if (trim($one) === '') {
                throw ARevertCannotBeShown::anUnnamedConnection();
            }
        }

        return new self(array_values($named));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->named);
    }

    public function count(): int
    {
        return count($this->named);
    }
}
