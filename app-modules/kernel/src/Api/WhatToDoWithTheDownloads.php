<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Pausing every download client the stack runs, or letting them fetch again.
 *
 * One stack-wide act each way, rehearsed before it is carried out: the
 * rehearsal says what every client is doing and names the offer the yes quotes,
 * so what is paused is what was shown.
 */
enum WhatToDoWithTheDownloads: string implements AnAction
{
    /** Stop every client fetching. */
    case Pause = 'pause';

    /** Let every client fetch again. */
    case Resume = 'resume';

    /** lemonfiber's word for it, which this app's word is allowed to differ from. */
    public function asked(): string
    {
        return match ($this) {
            self::Pause => 'downloads-pause',
            self::Resume => 'downloads-resume',
        };
    }
}
