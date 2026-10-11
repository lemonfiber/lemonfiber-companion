<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function sprintf;

/**
 * Where one name sits beneath another in an answer, as a refusal names it: `clients.0.was`.
 *
 * One spelling for every reader that names a place inside a list, so a refusal
 * reads the same whichever envelope it came from.
 */
final readonly class APath
{
    /** The name, beneath the place it sits in. */
    public static function beneath(string $where, int|string $name): string
    {
        return sprintf('%s.%s', $where, $name);
    }
}
