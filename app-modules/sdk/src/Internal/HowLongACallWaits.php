<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Lemonfiber\Sdk\Time\Duration;
use Modules\Kernel\Api\Timeout;

/**
 * How long a call to a stack waits, as the SDK is told it.
 *
 * The bound is {@see Timeout}'s, in the seconds a person feels; the SDK takes a
 * {@see Duration}. This is the edge where one becomes the other, so the number
 * is decided once and every connection this app opens waits the same.
 *
 * The SDK holds a call to it whole: every attempt at one request shares the
 * wait the first began, so an answer that never comes gives the screen back
 * once the wait is over, however many times the request was asked.
 */
final readonly class HowLongACallWaits
{
    /** The wait every call to a stack is given. */
    public static function ordinarily(): Duration
    {
        return Duration::ofSeconds(Timeout::ordinary()->inSeconds());
    }
}
