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
 * What the services involved were saying when a walkthrough stopped, line by line.
 *
 * Carried as given, blank lines included: a log line is the service's text and
 * not a sentence this app holds to anything.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhatTheServicesWereSaying implements Countable, IteratorAggregate
{
    /** @param list<string> $lines */
    private function __construct(private array $lines) {}

    public static function of(string ...$lines): self
    {
        return new self(array_values($lines));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->lines);
    }

    public function count(): int
    {
        return count($this->lines);
    }
}
