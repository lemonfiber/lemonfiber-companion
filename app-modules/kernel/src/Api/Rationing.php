<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack how it shares its line with the household.
 *
 * **It reads and changes nothing.** Changing the limits, the cap and the
 * household's hours is {@see SharingTheLine}'s. It takes a stack and a
 * session rather than a client, for the reason {@see Asking} gives.
 */
interface Rationing
{
    /** Ask a stack how its line is shared, or come away with a reason. */
    public function rationedOn(Stack $stack, Session $session): WhatTheLineWasFound;
}
