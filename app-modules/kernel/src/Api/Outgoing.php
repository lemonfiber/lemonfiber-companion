<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what leaves it.
 *
 * The question somebody asks when they want to know what their machine says to
 * the world while nobody is watching: every request lemonfiber makes on its
 * own account, and what each of its services reaches.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives. **It reads and does not switch anything off**: turning
 * a connection off is a setting, changed where settings are changed.
 */
interface Outgoing
{
    /** Ask a stack what leaves it, or come away with a reason (`C1`). */
    public function leaving(Stack $stack, Session $session): WhatWasFoundLeaving;
}
