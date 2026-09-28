<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack to wire its services to each other, and following the run.
 *
 * A wiring run is safe to run again: it changes nothing already right and
 * keeps what the operator changed. So it is offered as the act it is, with no
 * question before it. The stack answers with work to follow, and what the run
 * came to arrives through {@see self::whatBecameOf()}.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives, and answers a value rather than raising (`C1`).
 */
interface WiringTheServices
{
    /** Start a wiring run. */
    public function wire(Stack $stack, Session $session): WhatBecameOfTheWiring;

    /** What became of the run, by the handle it answered with. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWiring;
}
