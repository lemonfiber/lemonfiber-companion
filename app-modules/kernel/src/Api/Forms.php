<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * The groupings a stack arranges its services into.
 *
 * A typed collection rather than an array, which is `D1` — and the rule is
 * right here in a way that is easy to argue against. A form is a name and
 * nothing else, so a collection around it looks like a class whose only method
 * returns what it was given. What it actually buys is that the list carries its
 * own meaning across a module boundary: `list<string>` in a signature could be
 * ids, names, or anything, and the knowledge of which lives in whoever last
 * wrote a foreach.
 *
 * **Carried beside the services rather than derived from them.** A form with
 * nothing running in it still exists, and it is the one an operator most wants:
 * a stack whose whole media form is stopped has a form to start, and a list
 * built from the running services would be missing exactly that one.
 *
 * @implements IteratorAggregate<int, Form>
 */
final readonly class Forms implements IteratorAggregate
{
    /** @param array<int, Form> $forms */
    private function __construct(private array $forms) {}

    /**
     * The forms a stack declared, in the order it declared them.
     *
     * Not habit on the reindex: a variadic collected from named arguments has
     * string keys, and everything below reads this by position.
     */
    public static function these(Form ...$forms): self
    {
        return new self(array_values($forms));
    }

    /** A stack that arranges nothing into forms. */
    public static function none(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->forms);
    }

    /** @return Traversable<int, Form> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->forms);
    }
}
