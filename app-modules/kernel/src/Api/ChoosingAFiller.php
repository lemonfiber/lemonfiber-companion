<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Choosing which service fills a capability, asked of the stack rather than decided here.
 *
 * Apart from {@see Linking} on purpose: a port that both read the wiring and
 * changed it would hand every screen that only looks the means to change it.
 *
 * **Two methods, because the wire has two calls and they are different acts.**
 * Without a yes the stack works the choice out and writes nothing; with the
 * name of that reading it works it out again and makes it only where the two
 * agree. Both answer {@see WhatBecameOfTheFill} rather than raising: a stack
 * that is asleep and a stack that declines are both ordinary states.
 */
interface ChoosingAFiller
{
    /** What filling this capability with this service would come to, without making the choice. */
    public function whatItWouldComeTo(Stack $stack, Session $session, Capability $capability, ServiceId $service): WhatBecameOfTheFill;

    /** Make the choice, having been shown what it comes to and agreed. */
    public function choose(Stack $stack, Session $session, AFillAgreed $agreed): WhatBecameOfTheFill;
}
