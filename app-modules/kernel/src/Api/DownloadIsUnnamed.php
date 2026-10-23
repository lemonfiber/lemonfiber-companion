<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A download the stack said is moving arrived with nothing saying what it is.
 *
 * Refused rather than listed: a row with no name is a download an operator
 * would be told is interrupted without being told which.
 */
final class DownloadIsUnnamed extends InvalidArgumentException
{
    public static function amongThoseMoving(): self
    {
        return new self('A download the stack said is moving arrived with no name, and a download nobody can name is not one to warn about.');
    }
}
