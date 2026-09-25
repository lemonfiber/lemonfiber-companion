<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack how full its machine is, where the room went, and what is on its disk.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface Measuring
{
    /**
     * Ask a stack how full it is, or come away with a reason.
     *
     * Answers {@see WhatWasMeasured} rather than raising, which `C1` requires.
     */
    public function measuredOn(Stack $stack, Session $session): WhatWasMeasured;
}
