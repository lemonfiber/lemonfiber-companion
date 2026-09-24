<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_filter;
use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Every word lemonfiber explains, in the order the stack lists them.
 *
 * @implements IteratorAggregate<int, AWord>
 */
final readonly class TheGlossary implements Countable, IteratorAggregate
{
    /** @param list<AWord> $words */
    private function __construct(private array $words) {}

    /**
     * In the stack's order.
     *
     * Reindexed, because a spread of named arguments keeps its string keys and
     * a list is what the iterator promises.
     */
    public static function of(AWord ...$words): self
    {
        return new self(array_values($words));
    }

    /** The words whose names hold what somebody is looking for, or every word where nothing is. */
    public function matching(LookingFor $looking): self
    {
        if (! $looking->isSearching()) {
            return $this;
        }

        return new self(array_values(array_filter(
            $this->words,
            static fn(AWord $word): bool => $word->answers($looking),
        )));
    }

    /** The words that explain a word a screen drew: none where the glossary does not carry it. */
    public function explaining(AWordInUse $word): self
    {
        return new self(array_values(array_filter(
            $this->words,
            static fn(AWord $entry): bool => $entry->explains($word),
        )));
    }

    /** @return Traversable<int, AWord> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->words);
    }

    public function count(): int
    {
        return count($this->words);
    }
}
