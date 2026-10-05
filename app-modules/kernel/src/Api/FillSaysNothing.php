<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A choice of what fills a capability arrived with something it had to say left blank.
 *
 * Refused at construction, because a reading with no name is one no yes can
 * quote, and a yes that quotes nothing is consent nobody read for.
 */
final class FillSaysNothing extends InvalidArgumentException
{
    /** A word the reading owes was blank. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A choice of what fills a capability arrived with its `%s` blank, and a reading that cannot be named cannot be agreed to.',
            $field,
        ));
    }
}
