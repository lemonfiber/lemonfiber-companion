<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A record arrived without saying how far back it goes.
 *
 * Refused rather than drawn, because this is the whole of why the horizon is
 * a field: a record that has been trimmed and one that has always been short
 * look identical from their entries alone, and only one of them means
 * something is missing. A record with no horizon would draw its last row as
 * the machine's first day.
 */
final class TheRecordHasNoHorizon extends InvalidArgumentException
{
    public static function said(): self
    {
        return new self('A record arrived without saying how far back it goes. Its oldest entry would read as the beginning of the machine rather than as the end of what is kept, so it is refused rather than shown.');
    }
}
