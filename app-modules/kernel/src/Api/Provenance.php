<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack where its services come from.
 *
 * The question somebody asks when they want to know what they are actually
 * running: which image, pinned at what, built from which project and under
 * whose licence. It is the other half of what a stack keeps about itself —
 * {@see History} says what was done, and this says what it was done with.
 *
 * **It takes a stack and a session rather than a client**, for the reason
 * {@see Asking} gives: each stack's session is separate and every connection is
 * pinned against that stack's fingerprint.
 *
 * **It asks the stack and nothing else.** No upstream is ever reached from
 * here, so a project that has gone away changes nothing this answers.
 */
interface Provenance
{
    /**
     * Ask a stack where its services come from, or come away with a reason.
     *
     * Answers {@see WhatTheOriginsWere} rather than raising, which `C1`
     * requires.
     */
    public function declaredOn(Stack $stack, Session $session): WhatTheOriginsWere;
}
