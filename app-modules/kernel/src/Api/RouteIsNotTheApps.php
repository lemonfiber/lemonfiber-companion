<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A notification was asked to lead somewhere that is not a screen of this app.
 */
final class RouteIsNotTheApps extends InvalidArgumentException
{
    public static function named(): self
    {
        return new self('A notification can only lead to a screen of this app, named by a route from its root.');
    }
}
