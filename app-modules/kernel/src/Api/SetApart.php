<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;

use function in_array;

use IteratorAggregate;
use Traversable;

/**
 * Every event kind set apart from the preset, in the stack's order.
 *
 * **Each kind appears once.** A kind set apart twice would carry two answers
 * about whether it is heard, and whichever a screen drew, the other would be
 * the one the machine acts on.
 *
 * @implements IteratorAggregate<int, AnEventSetApart>
 */
final readonly class SetApart implements Countable, IteratorAggregate
{
    /** @param list<AnEventSetApart> $events */
    private function __construct(private array $events) {}

    /** These events, in the stack's order; reindexed for a named spread's keys. */
    public static function of(AnEventSetApart ...$events): self
    {
        $seen = [];

        foreach ($events as $event) {
            if (in_array($event->kind(), $seen, strict: true)) {
                throw AlertSaysNothing::twice($event->kind());
            }

            $seen[] = $event->kind();
        }

        return new self(array_values($events));
    }

    /** @return Traversable<int, AnEventSetApart> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->events);
    }

    public function count(): int
    {
        return count($this->events);
    }
}
