<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether the request service has been told about somebody.
 *
 * The account they watch with and the one they ask with live on two services,
 * and the second can be down while the first is not. *Not yet* is neither
 * done nor failed: the next run tells it. *Not tried* is a rehearsal, or a
 * stack with no request service at all, and nothing a later run puts right.
 */
enum WhetherTheyCanAsk: string
{
    /** The request service holds an account for them. */
    case Made = 'made';

    /** The request service could not be told yet; the next run tells it. */
    case NotYet = 'not-yet';

    /** Nothing was tried. */
    case NotTried = 'not-tried';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.invitation.asking.%s', $this->value);
    }
}
