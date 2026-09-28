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
 * Whether a reading of what taking lemonfiber off reaches is complete, and what could not be read.
 *
 * **The most important sentence on the screen.** A list that is short says so
 * and says why, rather than reading as a machine with less on it than it has.
 * Whether it is complete is the stack's word, carried as it came; what could
 * not be read is each in the words of whatever refused.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class HowMuchWasRead implements Countable, IteratorAggregate
{
    /** @param list<string> $unread */
    private function __construct(private bool $complete, private array $unread) {}

    /** Every source the removal needed answered. */
    public static function everything(string ...$unread): self
    {
        return new self(complete: true, unread: self::said(...$unread));
    }

    /** Not every source answered, and these could not be read. */
    public static function notEverything(string ...$unread): self
    {
        return new self(complete: false, unread: self::said(...$unread));
    }

    /** Whether the stack says every source answered. */
    public function isComplete(): bool
    {
        return $this->complete;
    }

    /** @return Traversable<int, string> what could not be read, in the words of whatever refused */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->unread);
    }

    public function count(): int
    {
        return count($this->unread);
    }

    /**
     * Each sentence, refused blank.
     *
     * @return list<string>
     */
    private static function said(string ...$unread): array
    {
        foreach ($unread as $one) {
            if (trim($one) === '') {
                throw UninstallSaysNothing::about('confidence.unread');
            }
        }

        return array_values($unread);
    }
}
