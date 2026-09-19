<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * What a member is told, in the order the core said it.
 *
 * A typed collection rather than an array, which is what `D1` asks for and what
 * makes the order part of the promise: the core writes these as a reading and
 * the sequence is part of the reading — what happens to what you ask for, then
 * what your period has left, then when it makes room. A surface that re-ordered
 * them would be editing an answer it did not write.
 *
 * Empty is a legitimate value and not an error, which is {@see Remedies}'
 * argument and carries more weight here: a member with nothing to be told is an
 * ordinary member, and the answer *there is nothing to tell you* is one a screen
 * says in as many words. What it must never be confused with is a stack that
 * would not say, and that distinction is kept one level up in
 * {@see WhatTheyAreOwed} rather than by an empty collection standing for both.
 *
 * There is deliberately no `count()`. A screen asking how many there are is a
 * screen about to write its own sentence about the number, and the whole point
 * of this type is that the core wrote every sentence. What a template does with
 * an empty one is draw the line that says so, which is the `@empty` arm of the
 * loop it was already walking.
 *
 * @implements IteratorAggregate<int, Sentence>
 */
final readonly class Sentences implements IteratorAggregate
{
    /** @param array<int, Sentence> $said */
    private function __construct(private array $said) {}

    public static function of(Sentence ...$said): self
    {
        // Not habit: a variadic collected from *named* arguments has string
        // keys, so a collection built that way would not be a list — and
        // `count()` beside an iteration that assumes position would then
        // disagree with what a template walked.
        return new self(array_values($said));
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->said);
    }
}
