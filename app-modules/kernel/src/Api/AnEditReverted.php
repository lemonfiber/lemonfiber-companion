<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use ArrayIterator;

use function count;

use Countable;

use function explode;

use IteratorAggregate;

use function mb_strlen;
use function mb_substr;
use function str_starts_with;

use Traversable;

use function trim;

/**
 * One file the operator edited, and the lines putting it back changes.
 *
 * The stack's diff holds the operator's lines marked `-` and lemonfiber's
 * marked `+`, with the matching head and tail left out. It is read line by
 * line here, and a line carrying neither mark is refused rather than drawn: a
 * line an operator cannot place on either side tells them nothing about what
 * they would lose.
 *
 * A file with no lines is one whose difference no line can show, which the
 * stack says with an empty diff.
 *
 * @implements IteratorAggregate<int, ALineOfADiff>
 */
final readonly class AnEditReverted implements Countable, IteratorAggregate
{
    /** How the stack marks the operator's line. */
    private const string THEIRS = '- ';

    /** How the stack marks lemonfiber's line. */
    private const string LEMONFIBERS = '+ ';

    /** @param list<ALineOfADiff> $lines */
    private function __construct(private string $path, private array $lines) {}

    /** The file at `$path` in the stack directory, and the diff the stack gave for it. */
    public static function at(string $path, string $diff): self
    {
        if (trim($path) === '') {
            throw ARevertCannotBeShown::withoutAPath();
        }

        $lines = [];

        foreach (explode("\n", $diff) as $position => $line) {
            if ($line !== '') {
                $lines[] = self::marked($line, $position);
            }
        }

        return new self($path, $lines);
    }

    /** The file's path within the stack directory. */
    public function path(): string
    {
        return $this->path;
    }

    /** @return Traversable<int, ALineOfADiff> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->lines);
    }

    public function count(): int
    {
        return count($this->lines);
    }

    /** One line of the diff, by its mark. */
    private static function marked(string $line, int $position): ALineOfADiff
    {
        if (str_starts_with($line, self::THEIRS)) {
            return ALineOfADiff::theirs(mb_substr($line, mb_strlen(self::THEIRS)));
        }

        if (str_starts_with($line, self::LEMONFIBERS)) {
            return ALineOfADiff::lemonfibers(mb_substr($line, mb_strlen(self::LEMONFIBERS)));
        }

        throw ARevertCannotBeShown::unmarked($position);
    }
}
