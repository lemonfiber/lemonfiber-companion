<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Why the queue classified a download as unhealthy.
 *
 * The value is the wire's word.
 */
enum WhyItIsWedged: string
{
    /** Connected, with nothing moving and no source to move it. */
    case Stalled = 'stalled';

    /** Moving, but so slowly its estimate runs past what is allowed. */
    case Slow = 'slow';

    /** The client or the curator gave up on it. */
    case Failed = 'failed';

    /** A torrent that has not resolved what it is. */
    case StuckOnMetadata = 'stuck-on-metadata';

    /** In a client under a curator's category, and in no curator's queue. */
    case Orphaned = 'orphaned';

    /** The key for this class, on the queue screen. */
    public function saidOnTheScreen(): string
    {
        return sprintf('queue.class.%s', $this->value);
    }
}
