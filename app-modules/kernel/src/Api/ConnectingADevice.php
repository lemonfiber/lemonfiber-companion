<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app asks of a stack about one person's device.
 *
 * One act: handing the device the way onto the media server, which also says
 * whether a device of theirs has arrived since the code was given.
 */
enum ConnectingADevice: string
{
    /** Give the code, or say where handing it over stands. */
    case HandOver = 'hand_over';

    /** lemonfiber's word for it, which this app's word is allowed to differ from. */
    public function asked(): string
    {
        return match ($this) {
            self::HandOver => 'household-handoff',
        };
    }
}
