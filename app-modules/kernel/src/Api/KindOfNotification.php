<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which sort of thing an alert tells the operator, from the closed list the core publishes.
 *
 * Each stack says which of these it raises, and the notification page offers
 * a switch for each of those and for no other: the kinds are the core's, never
 * the app's. Declared in the contract's order, which is the order the page
 * draws them in. The value is the wire's word and what a kept choice names the
 * kind by.
 */
enum KindOfNotification: string
{
    /** A condition at warning or worse, at its onset and its resolution. */
    case Problem = 'problem';

    /** Work the operator asked for, finished. */
    case Completion = 'completion';

    /** An update to the stack or to lemonfiber, available. */
    case Update = 'update';

    /** A backup that succeeded. */
    case Backup = 'backup';

    /** A household member's request, newly waiting on the operator. */
    case Request = 'request';

    /** An invitation accepted, declined or lapsed. */
    case Member = 'member';

    /** The key for what the kind is called on the notification page. */
    public function saidOnTheScreen(): string
    {
        return sprintf('notifications.kind.%s', $this->value);
    }
}
