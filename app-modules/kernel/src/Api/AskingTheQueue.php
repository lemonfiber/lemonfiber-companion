<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack how its queue heals itself.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface AskingTheQueue
{
    /** Ask a stack how its queue heals itself, or come away with a reason. */
    public function howItHeals(Stack $stack, Session $session): WhatTheQueueSaid;
}
