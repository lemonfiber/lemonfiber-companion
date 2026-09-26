<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * The one word the core's health summary opens with, as the core computed it.
 *
 * Eight rather than the four a doctor report's verdict has, because the core
 * knows things about a stack that no report says: that it was stopped on
 * purpose, that it was never set up, that it is still coming up, and that what
 * failed is only something nobody depends on. The app renders the word and
 * decides none of it.
 */
enum HowItStands: string
{
    case Healthy = 'healthy';

    case Stopped = 'stopped';

    case Unconfigured = 'unconfigured';

    case Advisory = 'advisory';

    case Degraded = 'degraded';

    case Broken = 'broken';

    case Critical = 'critical';

    case Unknown = 'unknown';

    public function saidOnTheScreen(): string
    {
        return sprintf('health.standing.%s', $this->value);
    }
}
