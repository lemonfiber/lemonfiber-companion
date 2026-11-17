<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How a run-and-exit service's last run went, as its health.
 *
 * The value is the wire's word.
 */
enum HowItsLastRunWent: string
{
    /** It has not run yet. */
    case NotRun = 'not-run';

    /** Its last run exited zero. */
    case Succeeded = 'succeeded';

    /** Its last run did not exit zero. */
    case Failed = 'failed';

    /** The key for this health, on the schedule screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('scheduled.health.%s', $this->value);
    }
}
