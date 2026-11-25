<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A `copying` envelope did not hold what the contract says one holds.
 *
 * Refused rather than read short: a copy drawn without how far it has got or
 * whether it is stalled would tell the operator something the stack did not.
 */
final class CopyingIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is, at the path named. */
    public static function missing(string $where): self
    {
        return new self(sprintf(
            'The answer on what is being copied has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $where,
        ));
    }
}
