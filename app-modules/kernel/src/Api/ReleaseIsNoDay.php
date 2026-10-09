<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/** A release day the calendar does not have. */
final class ReleaseIsNoDay extends InvalidArgumentException
{
    public static function onTheCalendar(): self
    {
        return new self('A title came out on a day the calendar has, and this is not one.');
    }
}
