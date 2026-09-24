<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack which copies of itself this machine holds.
 *
 * The half of putting one back that comes before naming it: a restore is asked
 * for by name, and a phone has nowhere to look a name up but here. It takes a
 * stack and a session rather than a client, for the reason {@see Asking} gives.
 */
interface Copying
{
    /**
     * Ask a stack which copies it holds, or come away with a reason.
     *
     * Answers {@see WhatCopiesWereFound} rather than raising, which `C1`
     * requires.
     */
    public function copiesOn(Stack $stack, Session $session): WhatCopiesWereFound;
}
