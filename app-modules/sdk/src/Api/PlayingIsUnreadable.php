<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `playing` envelope did not hold what the contract says one holds.
 *
 * Refused rather than read short: a session drawn without its member or its
 * device would tell the operator somebody is watching something somewhere.
 */
final class PlayingIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is, at the path named. */
    public static function missing(string $where): self
    {
        return new self(sprintf(
            'The answer on what is playing has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $where,
        ));
    }
}
