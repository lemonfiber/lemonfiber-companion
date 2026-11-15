<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what its outside watcher sees.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface AskingTheWatcher
{
    /** Ask a stack what its watcher sees, or come away with a reason. */
    public function whatItSees(Stack $stack, Session $session): WhatTheWatcherSaid;
}
