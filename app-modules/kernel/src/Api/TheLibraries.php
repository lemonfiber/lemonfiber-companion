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
 * Libraries named as the operator names them, in the order they were named.
 *
 * None at all is an answer rather than a gap: an invitation naming no library
 * is one that opens every library, and an answer carrying none says that is
 * what was written.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class TheLibraries implements Countable, IteratorAggregate
{
    /** @param list<string> $named */
    private function __construct(private array $named) {}

    /** These libraries; a blank name is refused, and the list is reindexed for a named spread's keys. */
    public static function of(string ...$named): self
    {
        foreach ($named as $library) {
            if (trim($library) === '') {
                throw InvitationSaysNothing::about('libraries');
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
