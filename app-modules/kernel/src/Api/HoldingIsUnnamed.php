<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A holding arrived with nothing to identify it by.
 *
 * Its own kind for the reason {@see ServiceIsUnnamed} is one: the identifier is
 * what a holding is asked for by, so one that is blank is a row nothing can be
 * done with rather than a row missing a label.
 */
final class HoldingIsUnnamed extends InvalidArgumentException
{
    public static function whereOneWasExpected(): self
    {
        return new self('A holding arrived with no identifier.');
    }
}
