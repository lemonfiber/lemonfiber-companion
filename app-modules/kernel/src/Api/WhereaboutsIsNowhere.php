<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * The operator was somewhere, and nothing said where.
 *
 * Refused rather than defaulted, because every plausible default is the
 * failure described. "Nowhere" restores nothing and the operator
 * signs in to find their work gone; "the first screen" is the bounce-to-login
 * the requirement exists to forbid, wearing a different name.
 *
 * The blank string is the one that would actually happen — a screen naming
 * itself from a value that was not set yet. It reads as a screen right up to
 * the moment somebody is returned to it.
 */
final class WhereaboutsIsNowhere extends InvalidArgumentException
{
    public static function named(): self
    {
        return new self('A screen was named with a blank string, and an operator returned to a blank screen has lost what they were doing.');
    }
}
