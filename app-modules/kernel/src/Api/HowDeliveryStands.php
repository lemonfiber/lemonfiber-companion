<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** Where delivering alerts stands as a whole on one machine. */
enum HowDeliveryStands: string
{
    /** No back-end is set up. */
    case Unconfigured = 'unconfigured';

    /** Every back-end delivers. */
    case Ready = 'ready';

    /** Some back-ends are failing. */
    case Degraded = 'degraded';

    /** Nothing can be delivered. */
    case Unreachable = 'unreachable';

    /** The key for what this is called on the screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('alerts.state.%s', $this->value);
    }
}
