<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What time it is, asked rather than taken.
 *
 * The first of this application's ports, and the smallest one worth having.
 * Time is a hidden input: `time()` returns a different answer tomorrow without
 * its arguments changing, so a test cannot pin it and the code around it
 * either goes untested or becomes slow and flaky. Behind a port, "the session
 * expired", "the backup is three days old" and "retry after thirty seconds"
 * are things a test simply states (B1).
 *
 * Named for what it does rather than for being an interface, and it says
 * nothing about where the time comes from: the device adapter reads the
 * platform's clock, and a test hands over a frozen one. Neither is named here,
 * which is what makes the second one possible.
 */
interface Clock
{
    /** The current moment, to the second. */
    public function now(): Instant;
}
