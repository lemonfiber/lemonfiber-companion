<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;

use function count;

use Countable;
use IteratorAggregate;

use function mb_strlen;
use function preg_match;

use Traversable;

/**
 * A code another device can scan off this one's screen, as the rows of squares it is drawn with.
 *
 * Each row is a line of `1` for a dark square and `0` for a light one, and the
 * rows make a square: as many of them as each is long. It is a picture of the
 * text it was made from and nothing else, so it carries no text of its own —
 * what it says is whatever was handed to {@see Encoding}.
 *
 * None at all is a code that could not be drawn, which a screen says in words
 * rather than drawing an empty square somebody would try to scan.
 *
 * @implements IteratorAggregate<int, string>
 */
final readonly class AScannableCode implements Countable, IteratorAggregate
{
    /** @param list<string> $rows */
    private function __construct(private array $rows) {}

    /** These rows, top to bottom; anything but a square of `1` and `0` is refused. */
    public static function drawn(string ...$rows): self
    {
        $size = count($rows);

        foreach ($rows as $row) {
            if (mb_strlen($row) !== $size || preg_match('/\A[01]+\z/', $row) !== 1) {
                throw CodeIsNotSquare::withRowsOf($size);
            }
        }

        return new self(array_values($rows));
    }

    /** No code, because none could be drawn. */
    public static function none(): self
    {
        return new self([]);
    }

    /** @return Traversable<int, string> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rows);
    }

    /** How many squares along each side, and none where there is no code. */
    public function count(): int
    {
        return count($this->rows);
    }
}
