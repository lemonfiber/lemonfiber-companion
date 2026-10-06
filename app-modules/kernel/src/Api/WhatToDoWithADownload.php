<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app may ask a stack to do about one completed download.
 *
 * A closed set for {@see WhatToChange}'s reason: an action's name is the last
 * segment of its path, and a name spelled at the call site is this app able to
 * ask for any action a stack offers.
 */
enum WhatToDoWithADownload: string implements AnAction
{
    /** Stop seeding it, or say what stopping would cost. */
    case StopSeeding = 'stop_seeding';

    /** lemonfiber's word for it, which this app's word is allowed to differ from. */
    public function asked(): string
    {
        return match ($this) {
            self::StopSeeding => 'stop-seeding',
        };
    }
}
