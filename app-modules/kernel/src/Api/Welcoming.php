<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack where the household comes in: its front door, and what else they can reach.
 *
 * A read. It names no door and builds no address. It takes a stack and a
 * session rather than a client, for the reason {@see Asking} gives.
 */
interface Welcoming
{
    /** Ask a stack for its front door, or come away with a reason (`C1`). */
    public function frontDoorOf(Stack $stack, Session $session): WhatWasFoundOfTheFrontDoor;
}
