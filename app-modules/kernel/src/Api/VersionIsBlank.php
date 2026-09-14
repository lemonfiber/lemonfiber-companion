<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A release arrived without a version to call it by.
 *
 * Its own exception rather than a generic one, for the reason every refusal
 * here has its own: what an operator is told depends on which half of the
 * conversation went wrong, and a payload short of a field is the stack's half.
 */
final class VersionIsBlank extends InvalidArgumentException
{
    public static function inARelease(): self
    {
        return new self('A release arrived with no version to call it by.');
    }
}
