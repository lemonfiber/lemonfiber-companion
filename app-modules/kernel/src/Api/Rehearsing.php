<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what starting a form would come to, without starting it.
 *
 * A read: the stack rehearses the start and changes nothing, so asking twice
 * is asking twice and nothing more.
 */
interface Rehearsing
{
    /**
     * What starting that form would bring up and leave out, or a reason.
     *
     * Answers {@see WhatTheRehearsalFound} rather than raising, which `C1`
     * requires.
     */
    public function whatStarting(Stack $stack, Session $session, Form $form): WhatTheRehearsalFound;
}
