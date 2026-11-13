<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/** The answer saying which ways a member is told on could not be read. */
final class BeingToldIsUnreadable extends InvalidArgumentException
{
    /** A field the answer carries is absent, or not what the contract says it is, at the path named. */
    public static function missing(string $where): self
    {
        return new self(sprintf(
            'The answer saying how a member is told has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $where,
        ));
    }
}
