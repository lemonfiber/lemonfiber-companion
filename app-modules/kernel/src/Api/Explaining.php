<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what lemonfiber's words mean.
 *
 * A read, answered from a table compiled into the stack's binary. It takes a
 * stack and a session rather than a client, for the reason {@see Asking} gives.
 */
interface Explaining
{
    /**
     * Ask a stack for its glossary, or come away with a reason.
     *
     * Answers {@see WhatWasFoundOfTheWords} rather than raising, which `C1`
     * requires.
     */
    public function glossaryOn(Stack $stack, Session $session): WhatWasFoundOfTheWords;
}
