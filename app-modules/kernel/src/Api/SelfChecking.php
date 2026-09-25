<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack about its own running copy of lemonfiber: which version, how it was installed, and whether a newer one exists.
 *
 * A read. It replaces nothing, and takes a stack and a session rather than a
 * client, for the reason {@see Asking} gives.
 */
interface SelfChecking
{
    /**
     * Ask a stack about its running copy, or come away with a reason.
     *
     * Answers {@see WhatWasFoundOfItself} rather than raising, which `C1`
     * requires.
     */
    public function checkedOn(Stack $stack, Session $session): WhatWasFoundOfItself;
}
