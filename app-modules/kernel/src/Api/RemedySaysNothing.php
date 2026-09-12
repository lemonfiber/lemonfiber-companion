<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A remedy arrived with nothing in it for the operator to do.
 *
 * Raised where a string becomes a `Remedy`, for the same reason `CodeIsBlank`
 * is: this is a value that cannot be constructed rather than a refusal
 * crossing a boundary, so there is nothing for a caller to open (C1, C3).
 */
final class RemedySaysNothing extends InvalidArgumentException
{
    public static function inAProblem(): self
    {
        return new self('A remedy arrived with no action in it, and a blank row renders as a button with no label.');
    }
}
