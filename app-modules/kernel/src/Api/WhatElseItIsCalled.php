<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_any;
use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;

use function mb_stripos;

use Traversable;

use function trim;

/**
 * What else a word is called, or the forms lemonfiber writes it in, in the
 * order the glossary lists them.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WhatElseItIsCalled implements Countable, IteratorAggregate
{
    /** @param list<string> $names */
    private function __construct(private array $names) {}

    /**
     * In the glossary's order; a blank name is refused.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(string ...$names): self
    {
        return self::listed('also_called', ...$names);
    }

    /** The forms lemonfiber writes the word in, such as `grabbed` for `grab`; a blank one is refused. */
    public static function formsOf(string ...$forms): self
    {
        return self::listed('forms', ...$forms);
    }

    /**
     * Whether any of these names holds what somebody is looking for.
     *
     * Case-insensitive, for {@see Said::holds()}'s reason.
     */
    public function answer(LookingFor $looking): bool
    {
        return array_any($this->names, static fn(string $name): bool => mb_stripos($name, $looking->typed()) !== false);
    }

    /** Whether one of these names is that word, whatever the case. */
    public function include(AWordInUse $word): bool
    {
        return array_any($this->names, static fn(string $name): bool => $word->is(AWordInUse::named($name)));
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

    private static function listed(string $field, string ...$names): self
    {
        foreach ($names as $name) {
            if (trim($name) === '') {
                throw WordsSayNothing::about($field);
            }
        }

        return new self(array_values($names));
    }
}
