<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A change to what the household may ask for that would ask the stack nothing it could act on.
 */
final class AllowanceChangeAsksNothing extends InvalidArgumentException
{
    /** A count below none. */
    public static function below(string $what, int $given): self
    {
        return new self(sprintf('A request limit was asked with %d %s, and a count cannot be below nothing.', $given, $what));
    }
}
