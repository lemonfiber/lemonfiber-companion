<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Changing what a stack is set to, asked of the stack rather than decided here.
 *
 * Apart from {@see Arranging} on purpose. A port whose one method both read and
 * wrote would hand every caller that only wanted to look the capability to
 * change, and the settings screen is mostly looked at.
 *
 * **Two methods, because the wire has two calls and they are different acts.**
 * The same action unconfirmed is a rehearsal and confirmed is a write, and the
 * core says so: *unconfirmed it is the review — the difference between what the
 * setting holds and what it would hold, and what changing it affects — so what
 * a browser agrees to is what it was shown*. Folding them into one method with
 * a boolean would be the positional flag this codebase refuses, on the one call
 * where reading it backwards writes to somebody's stack without being asked.
 */
interface Adjusting
{
    /**
     * What would happen if this setting were set to this, without setting it.
     *
     * Nothing is written. What comes back is where the change would stand and
     * what it would cost, which is what the operator is shown before being
     * asked anything.
     *
     * Answers {@see WhatTheStackMadeOfIt} rather than raising, which `C1`
     * requires: a stack that is asleep and a stack that declines are both
     * ordinary states of the world.
     */
    public function wouldBe(Stack $stack, Session $session, WhatToSet $asked): WhatTheStackMadeOfIt;

    /**
     * Set it, having been shown what that would come to and agreed.
     *
     * Separate from {@see wouldBe()} rather than a flag on it. The two differ
     * by one boolean on the wire and by everything to the person whose stack
     * it is.
     */
    public function agreedTo(Stack $stack, Session $session, WhatToSet $asked): WhatTheStackMadeOfIt;
}
