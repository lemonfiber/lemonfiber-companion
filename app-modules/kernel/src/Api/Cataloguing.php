<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what each of its services is for, and what became of any it dropped.
 *
 * The question behind a list of names: what does this one do for the house,
 * and what does the house go without while it is down. The stack reads the
 * answer out of its own description, so it answers with the services stopped.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives.
 */
interface Cataloguing
{
    /**
     * Ask a stack what its services are for, or come away with a reason.
     *
     * Answers {@see WhatTheCatalogueSaid} rather than raising, which `C1`
     * requires.
     */
    public function describedOn(Stack $stack, Session $session): WhatTheCatalogueSaid;
}
