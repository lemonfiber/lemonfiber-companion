<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use IteratorAggregate;
use Traversable;

/**
 * What stops working when one service does.
 *
 * A typed collection rather than an array, which is `D1`: an array has no name
 * and nowhere to put the rules, so what is in it lives in whoever last wrote a
 * foreach. Here that matters more than usual, because this list is the whole of
 * `N2-R8`'s sentence — *stopping this will also stop these* — and a screen
 * that got the shape wrong would either say nothing or say the wrong names.
 *
 * **By id rather than by name.** That is what the wire carries and what the
 * rest of a listing can be matched against; turning ids into names needs the
 * whole listing, which a single service does not have. The screen does that
 * lookup once, where it has both.
 *
 * Empty is the ordinary case and a real answer: most services have nothing
 * leaning on them, and *stopping this disturbs nothing else* is a sentence
 * worth being able to say.
 *
 * @implements IteratorAggregate<int, ServiceId>
 */
final readonly class WhatLeansOnIt implements IteratorAggregate
{
    /** @param array<int, ServiceId> $leaning */
    private function __construct(private array $leaning) {}

    /**
     * The services that stop with this one, in the order the stack listed them.
     *
     * Not habit: a variadic collected from named arguments has string keys, and
     * everything below reads this by position — the reindex
     * {@see Requested::of()} makes for the same reason.
     */
    public static function these(ServiceId ...$leaning): self
    {
        return new self(array_values($leaning));
    }

    /** Nothing leans on it, which is most services. */
    public static function nothing(): self
    {
        return new self([]);
    }

    public function count(): int
    {
        return count($this->leaning);
    }

    /** @return Traversable<int, ServiceId> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->leaning);
    }
}
