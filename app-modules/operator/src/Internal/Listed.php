<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function implode;

/**
 * Several names said as one line, in their order.
 */
final readonly class Listed
{
    /** What stands between two names in the line. */
    private const string BETWEEN = ', ';

    /** These names, in their order, as one line. */
    public static function these(string ...$names): string
    {
        return implode(self::BETWEEN, $names);
    }
}
