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
 * Things a move names by the stack's word for each: services it would stop, or the paths it would copy first.
 *
 * In the stack's order, and never with a blank among them: a service with no
 * name is one an operator cannot go and look at.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhatWasNamed implements Countable, IteratorAggregate
{
    /** @param list<string> $names */
    private function __construct(private array $names) {}

    /** These, in the stack's order; a blank is refused, named by the field it came from. */
    public static function of(string $field, string ...$names): self
    {
        foreach ($names as $name) {
            if (trim($name) === '') {
                throw TheMoveSaysNothing::about($field);
            }
        }

        return new self(array_values($names));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->names);
    }

    public function count(): int
    {
        return count($this->names);
    }
}
