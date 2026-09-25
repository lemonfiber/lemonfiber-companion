<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack where one item got to.
 *
 * A read. It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface Tracing
{
    /** Follow one item, or come away with a reason; answers rather than raising, which `C1` requires. */
    public function tracedOn(Stack $stack, Session $session, WhatToFollow $following): WhatWasFoundOfTheTrace;
}
