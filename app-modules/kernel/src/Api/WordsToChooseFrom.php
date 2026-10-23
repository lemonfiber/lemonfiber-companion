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
 * One list of words a quality choice may be made from, in the stack's order and as it wrote them.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class WordsToChooseFrom implements Countable, IteratorAggregate
{
    /** @param list<string> $words */
    private function __construct(private array $words) {}

    /** These words, listed under the name given; a blank one is refused, naming the list. */
    public static function under(string $list, string ...$words): self
    {
        foreach ($words as $word) {
            if (trim($word) === '') {
                throw QualitySaysNothing::about($list);
            }
        }

        return new self(array_values($words));
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->words);
    }

    public function count(): int
    {
        return count($this->words);
    }
}
