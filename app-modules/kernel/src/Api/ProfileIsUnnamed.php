<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A service's profile was named as nothing at all.
 *
 * Refused where the string becomes a {@see Profile}, for {@see FormIsUnnamed}'s
 * reason: a row saying a service belongs to a blank tells the operator less
 * than one saying nothing, and reads as though it had said something.
 */
final class ProfileIsUnnamed extends InvalidArgumentException
{
    public static function whereOneWasExpected(): self
    {
        return new self(
            'A profile was named as nothing at all, and a service said to belong to a blank is a row claiming to know something it does not.',
        );
    }
}
