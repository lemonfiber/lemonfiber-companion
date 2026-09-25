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
 * Everything that authenticates with one credential, each by name, in the stack's order.
 *
 * None at all is an answer: a credential nothing uses is one to remove, and it
 * is said to have no consumers rather than hidden.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhatUsesIt implements Countable, IteratorAggregate
{
    /** @param list<string> $consumers */
    private function __construct(private array $consumers) {}

    /** These consumers, in the stack's order; reindexed for a named spread's keys. */
    public static function of(string ...$consumers): self
    {
        foreach ($consumers as $consumer) {
            if (trim($consumer) === '') {
                throw CredentialSaysNothing::about('consumers');
            }
        }

        return new self(array_values($consumers));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->consumers);
    }

    public function count(): int
    {
        return count($this->consumers);
    }
}
