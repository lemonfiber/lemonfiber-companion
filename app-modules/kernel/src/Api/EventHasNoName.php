<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A stack's stream carried an event whose identifier is empty, which names nothing to resume after.
 *
 * Raised where a string becomes an {@see AnEventHeard}.
 */
final class EventHasNoName extends InvalidArgumentException
{
    public static function onTheStream(): self
    {
        return new self('A stream carried an event with an empty identifier, so there is nothing to resume after.');
    }
}
