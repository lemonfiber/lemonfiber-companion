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
 * The stack's reason for each item giving back room left behind, in its order, never blank.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhyItemsStayed implements Countable, IteratorAggregate
{
    /** @param list<string> $reasons */
    private function __construct(private array $reasons) {}

    /** These reasons, in the stack's order; a blank one is refused. */
    public static function because(string ...$reasons): self
    {
        foreach ($reasons as $why) {
            if (trim($why) === '') {
                throw RoomSaysNothing::about('why');
            }
        }

        return new self(array_values($reasons));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->reasons);
    }

    public function count(): int
    {
        return count($this->reasons);
    }
}
