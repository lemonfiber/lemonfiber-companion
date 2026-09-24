<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what it keeps on this machine, where, and why.
 *
 * The question a phone is least able to answer for itself: it has no
 * filesystem in front of it and cannot see the host at all. It takes a stack
 * and a session rather than a client, for the reason {@see Asking} gives.
 */
interface Storing
{
    /**
     * Ask a stack what it keeps, or come away with a reason.
     *
     * Answers {@see WhatWasFoundKept} rather than raising, which `C1` requires.
     */
    public function storedOn(Stack $stack, Session $session): WhatWasFoundKept;
}
