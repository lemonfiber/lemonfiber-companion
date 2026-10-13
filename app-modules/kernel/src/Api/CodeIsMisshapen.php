<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A notification decision arrived with something other than a code where its code belongs.
 *
 * The words it is shown in are keyed by the code and the code is shown beside
 * them, so anything else there would put the stack's own words on the
 * operator's screen. The value is not repeated here, for the same reason.
 */
final class CodeIsMisshapen extends InvalidArgumentException
{
    public static function inAnAlert(): self
    {
        return new self('A notification decision arrived with a code that is not lower-case words joined by dots, so it was not shown.');
    }
}
