<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack which app to watch on, device by device.
 *
 * A read, and the same answer on every machine. It takes a stack and a
 * session rather than a client, for the reason {@see Asking} gives.
 */
interface Advising
{
    /** Ask a stack which app to watch on, or come away with a reason (`C1`). */
    public function advisedBy(Stack $stack, Session $session): WhatWasFoundToWatchOn;
}
