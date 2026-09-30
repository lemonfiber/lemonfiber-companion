<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack which versions it runs, and what its running release changed.
 *
 * A read. It changes nothing, and takes a stack and a session rather than a
 * client, for the reason {@see Asking} gives.
 */
interface ReadingVersions
{
    /**
     * Ask a stack which versions it runs, or come away with a reason.
     *
     * Answers {@see WhatWasFoundOfTheVersions} rather than raising, which `C1`
     * requires.
     */
    public function versionsOn(Stack $stack, Session $session): WhatWasFoundOfTheVersions;
}
