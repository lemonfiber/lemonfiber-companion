<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack how it serves its web surface.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface AskingHowItIsServed
{
    /** Ask how it is served, or come away with a reason. */
    public function served(Stack $stack, Session $session): WhatWasSaidOfServing;
}
