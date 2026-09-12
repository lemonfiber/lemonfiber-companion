<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A command was built with nothing for the server to recognise a retry by.
 *
 * Raised where a string becomes an `IdempotencyKey`. A blank key does not fail
 * the send — it fails the *second* send, by letting it through as a new one,
 * which is the failure the type exists to prevent arriving quietly (C3).
 */
final class KeyIsBlank extends InvalidArgumentException
{
    public static function inACommand(): self
    {
        return new self('A command was built with a blank idempotency key, so the server has nothing to recognise a retry by.');
    }
}
