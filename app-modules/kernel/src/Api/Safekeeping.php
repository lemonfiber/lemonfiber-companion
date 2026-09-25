<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack which credentials it holds, where each stands, and what uses each.
 *
 * A read. It sets, changes and reveals nothing: a credential's value never
 * reaches this app. It takes a stack and a session rather than a client, for
 * the reason {@see Asking} gives.
 */
interface Safekeeping
{
    /** Ask a stack what credentials it holds, or come away with a reason (`C1`). */
    public function heldOn(Stack $stack, Session $session): WhatWasFoundOfTheCredentials;
}
