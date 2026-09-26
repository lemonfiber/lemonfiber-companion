<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What became of asking one service to act on a quality choice, in the stack's word for it.
 *
 * *Not started* is a service that was not ready and was asked nothing, which
 * is not a service that refused: asking again once it is up reaches it.
 */
enum WhereTheAskingStands: string
{
    /** The service accepted it, and it runs in the service's background. */
    case Started = 'started';

    /** The service had not finished starting, so nothing was asked of it. */
    case NotStarted = 'not-started';

    /** The service refused, or could not be reached. */
    case Failed = 'failed';
}
