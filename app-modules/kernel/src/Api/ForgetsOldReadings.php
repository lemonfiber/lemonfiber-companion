<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A store of readings, asked to let go of every one read before a moment.
 *
 * Every store that keeps a capability's readings implements this beside its
 * own port, and the composition root registers each one, so that letting go
 * of readings older than the operator chose asks one thing however many kinds
 * of reading the phone keeps. Nothing is opened to answer it: when a reading
 * was read stays readable beside the sealed payload for exactly this.
 */
interface ForgetsOldReadings
{
    /** Let go of every reading read before this moment; one read at it is kept. */
    public function forgetOlderThan(Instant $before): Forgotten;
}
