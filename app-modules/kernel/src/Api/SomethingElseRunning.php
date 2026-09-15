<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A container running on the machine that this stack's configuration does not
 * declare.
 *
 * A type of its own rather than a {@see Daemon} with a flag, because `N2-R21`
 * forbids presenting one as part of the stack and a flag is a thing a screen
 * can forget to read. Nothing here can be handed to a listing that expects a
 * service, so the refusal holds by construction rather than by everybody
 * remembering it.
 *
 * It carries no verb for the same reason. A `Daemon` can be started, stopped
 * and restarted; this cannot, and the way to guarantee that is to have nowhere
 * to put one — the requirement's *MUST NOT offer a verb against it* is then a
 * fact about the type rather than a rule about the screens.
 *
 * What it is running is the machine's own word for the image, not this app's
 * guess at what the image does. It is a plain string for {@see Daemon::$name}'s
 * reason — an identifier is worth a type because identifiers get mixed up, and
 * a description carries no invariant past *not blank*, which the reader settles
 * at the boundary.
 *
 * The state is `$runs` rather than `$state` so that it is the same word here as
 * on a {@see Daemon}. Two neighbouring types spending one word on two different
 * ideas is how somebody reads the wrong field and is not wrong enough to be
 * told.
 */
final readonly class SomethingElseRunning
{
    private function __construct(
        public WhatTheEngineCallsIt $id,
        public string $describes,
        public HowAServiceRuns $runs,
    ) {}

    /** One container, as the machine described it. */
    public static function called(WhatTheEngineCallsIt $id, string $describes, HowAServiceRuns $runs): self
    {
        return new self($id, $describes, $runs);
    }
}
