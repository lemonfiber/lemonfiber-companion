<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Where the running copy of lemonfiber stands against what has been released.
 *
 * *The check failed* is its own case and never reads as current: a copy nobody
 * could check is not a copy that is up to date.
 */
enum WhereThisCopyStands: string
{
    /** What is running is the newest released. */
    case Current = 'current';

    /** A newer version has been released. */
    case UpdateAvailable = 'update-available';

    /** Something other than lemonfiber owns this copy and replaces it. */
    case ManagedExternally = 'managed-externally';

    /** Whether a newer version exists could not be checked. */
    case CheckFailed = 'check-failed';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.itself.standing.%s', $this->value);
    }
}
