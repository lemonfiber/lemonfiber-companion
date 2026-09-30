<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether the device may raise its prompt without the operator asking for it.
 *
 * Unasked, where nothing is waiting: the lock screen comes up and the device
 * asks at once. Only on a tap, where an action this app already sent is still
 * waiting for its outcome, because a prompt raised over that is answered to be
 * rid of it rather than meant.
 */
enum WhenTheLockAsks
{
    case ByItself;

    case OnlyWhenTapped;

    /** Whether the device may ask before the operator taps. */
    public function byItself(): bool
    {
        return match ($this) {
            self::ByItself => true,
            self::OnlyWhenTapped => false,
        };
    }
}
