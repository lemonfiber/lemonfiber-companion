<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use Modules\Connection\Api\TheWayIn;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;

/**
 * The way that last let a member in, as it is written before it is sealed.
 */
final readonly class TheWayInAsKept
{
    /** The way, written down. */
    public static function written(TheWayIn $way): Unsealed
    {
        return Unsealed::of($way->value);
    }

    /** The way read back, or the password where what was written does not read as one. */
    public static function read(Shape $shape, Unsealed $value): TheWayIn
    {
        return match ($shape) {
            Shape::One => TheWayIn::tryFrom($value->inTheClear()) ?? TheWayIn::Password,
        };
    }
}
