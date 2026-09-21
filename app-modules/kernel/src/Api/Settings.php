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
 * Everything the stack is set to, in the order it said it.
 *
 * **The order is the stack's and is not sorted here.** A listing this app
 * re-ordered would be a second opinion about which settings matter, and the
 * operator reading the same stack through two interfaces would find them in two
 * arrangements with nothing saying why.
 *
 * **Countable, because a screen has to be able to say *none*.** A stack with
 * nothing set is an ordinary answer, and a template that draws an empty list
 * silently looks exactly like one that was never given an answer at all.
 *
 * @implements IteratorAggregate<int, Setting>
 */
final readonly class Settings implements Countable, IteratorAggregate
{
    /** @param array<int, Setting> $set */
    private function __construct(private array $set) {}

    public static function of(Setting ...$set): self
    {
        // `array_values` for the reason `Sentences::of()` gives: a variadic
        // collected from named arguments has string keys, and a collection
        // built that way is not a list — so `count()` and an iteration that
        // assumes position would disagree about the same object.
        return new self(array_values($set));
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->set);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->set);
    }
}
