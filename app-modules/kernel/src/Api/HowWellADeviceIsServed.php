<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How well one kind of device is served by the app recommended for it.
 *
 * *Fallback* is an answer and is drawn as one: nothing to install, works
 * anywhere, never unavailable. It is not the absence of a recommendation.
 */
enum HowWellADeviceIsServed: string
{
    /** An official app that works. */
    case Good = 'good';

    /** It works, with something worth knowing before starting. */
    case Workable = 'workable';

    /** Poorly served, with somewhere else to go. */
    case Poor = 'poor';

    /** Nothing to install, and it works anywhere. */
    case Fallback = 'fallback';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.clients.support.%s', $this->value);
    }
}
