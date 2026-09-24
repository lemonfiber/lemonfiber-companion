<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack how it shares its line with the household.
 *
 * **It reads and changes nothing.** Limits, caps and the household's hours are
 * configured where the core is configured; this app shows where they stand.
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface Rationing
{
    /** Ask a stack how its line is shared, or come away with a reason (`C1`). */
    public function rationedOn(Stack $stack, Session $session): WhatTheLineWasFound;
}
