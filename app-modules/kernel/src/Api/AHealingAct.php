<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * One of the three acts the queue takes on an item that struck out, each its own and never one.
 *
 * The value is the wire's word.
 */
enum AHealingAct: string
{
    /** Drop the download from its client. */
    case Remove = 'remove';

    /** Keep its release from being grabbed again. */
    case Blocklist = 'blocklist';

    /** Search for the title again. */
    case Search = 'search';

    /** The key for this act, on the queue screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('queue.act.%s', $this->value);
    }
}
