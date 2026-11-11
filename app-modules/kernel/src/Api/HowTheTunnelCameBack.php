<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether the tunnel came back with the stack, as the check after a start found it.
 *
 * The value is the wire's word.
 */
enum HowTheTunnelCameBack: string
{
    /** Its checks passed. */
    case Held = 'held';

    /** A check of it failed. */
    case NotEstablished = 'not-established';

    /** Nothing checked it: the stack runs no tunnel, did not come up, or could not be read. */
    case NotChecked = 'not-checked';

    /** The key for whether the tunnel came back, on the restart section. */
    public function saidOnTheScreen(): string
    {
        return sprintf('restarts.tunnel.%s', $this->value);
    }
}
