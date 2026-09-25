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
 * Every credential a stack holds, in the stack's order, with what their store protects against.
 *
 * None at all is an answer, and it is not *could not be read*: that is an
 * {@see Obstacle}, which {@see WhatWasFoundOfTheCredentials} keeps apart.
 *
 * @implements IteratorAggregate<int, ACredentialHeld>
 */
final readonly class TheCredentialsHeld implements Countable, IteratorAggregate
{
    /** @param list<ACredentialHeld> $held */
    private function __construct(private WhatTheStoreProtects $protection, private array $held) {}

    /** These credentials, in the stack's order; reindexed for a named spread's keys. */
    public static function of(WhatTheStoreProtects $protection, ACredentialHeld ...$held): self
    {
        return new self($protection, array_values($held));
    }

    /** What keeping them in files does and does not protect against. */
    public function protection(): WhatTheStoreProtects
    {
        return $this->protection;
    }

    /** @return Traversable<int, ACredentialHeld> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->held);
    }

    public function count(): int
    {
        return count($this->held);
    }
}
