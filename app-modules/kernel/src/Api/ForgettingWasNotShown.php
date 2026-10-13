<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A yes to removing everything lemonfiber keeps, given against something other than the listing of what would go.
 */
final class ForgettingWasNotShown extends InvalidArgumentException
{
    public static function first(): self
    {
        return new self('Removing everything lemonfiber keeps was agreed to without the list of what would go, and the yes is only sent with that list shown.');
    }
}
