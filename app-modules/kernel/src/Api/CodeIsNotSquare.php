<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A scannable code arrived with rows that do not make a square of dark and light squares.
 *
 * Refused rather than drawn: a code with one row short scans as nothing, or
 * worse, as something else.
 */
final class CodeIsNotSquare extends InvalidArgumentException
{
    /** Some row is not as long as there are rows, or holds something but `1` and `0`. */
    public static function withRowsOf(int $rows): self
    {
        return new self(sprintf(
            'A scannable code of %d rows has a row that is not %d squares of `1` and `0`.',
            $rows,
            $rows,
        ));
    }
}
