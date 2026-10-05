<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what it wires to what: which service answers each capability something asks for, and how that was settled.
 *
 * A read and nothing else. Choosing which service fills a capability is an
 * action of its own.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives.
 */
interface Linking
{
    /**
     * Ask a stack what it wires to what, or come away with a reason.
     *
     * Answers {@see WhatTheLinksSaid} rather than raising.
     */
    public function linkedOn(Stack $stack, Session $session): WhatTheLinksSaid;
}
