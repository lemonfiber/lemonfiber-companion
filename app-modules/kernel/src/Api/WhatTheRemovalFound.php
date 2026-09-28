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
 * What taking somebody out could not do, and anything else the stack says the operator should know, in its words.
 *
 * A request service that would not answer, or an account left there for the
 * next removal to take. Each is the stack's own sentence and none may be
 * blank.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhatTheRemovalFound implements Countable, IteratorAggregate
{
    /** @param list<string> $said */
    private function __construct(private array $said) {}

    /** These, in the stack's order; a blank one is refused. */
    public static function of(string ...$said): self
    {
        foreach ($said as $one) {
            if (trim($one) === '') {
                throw RemovalSaysNothing::about('findings');
            }
        }

        return new self(array_values($said));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->said);
    }

    public function count(): int
    {
        return count($this->said);
    }
}
