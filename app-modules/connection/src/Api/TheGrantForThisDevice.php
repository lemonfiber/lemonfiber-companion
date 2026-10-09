<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Granting;
use Modules\Kernel\Api\KeepingTheGrant;
use Modules\Kernel\Api\KnowingThisDevice;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheGrantIsFor;
use Modules\Kernel\Api\WhatTheGrantCameTo;
use Modules\Kernel\Api\Whose;

/**
 * The grant this device plays a member's titles with on one stack.
 *
 * The one kept is used while it stands, and only for the member and device id
 * it was answered for: a grant plays under the limits of the member it was
 * opened for, so another member signing in on the same stack is asked a grant
 * of their own. One that has lapsed, that the door refused, or that was kept
 * for somebody else is let go of and a new one asked for under this device
 * id, so the core replaces the session it opened before rather than leaving
 * two.
 *
 * A grant the store would not keep is still used for what is playing now: the
 * cost of not keeping it is one more ask of the core next time.
 */
final readonly class TheGrantForThisDevice
{
    public function __construct(
        private Granting $granting,
        private KeepingTheGrant $kept,
        private KnowingThisDevice $device,
        private Clock $clock,
    ) {}

    /** The grant kept for this member on this stack while it stands, or a new one where none does. */
    public function on(Stack $stack, Session $session, Whose $whose): WhatTheGrantCameTo
    {
        return $this->kept->theGrantOn($stack->id(), $this->for($whose))->either(
            held: fn(AGrant $held): WhatTheGrantCameTo => $held->hasLapsedBy($this->clock->now())
                ? $this->afresh($stack, $session, $whose)
                : WhatTheGrantCameTo::granted($held),
            none: fn(): WhatTheGrantCameTo => $this->afresh($stack, $session, $whose),
        );
    }

    /** A new grant, in place of whatever was kept: the door refused it, it lapsed, or it was somebody else's. */
    public function afresh(Stack $stack, Session $session, Whose $whose): WhatTheGrantCameTo
    {
        $this->kept->letTheGrantGo($stack->id());
        $device = $this->device->thisDevice();
        $for = TheGrantIsFor::of($whose, $device);

        return $this->granting->aGrantFor($stack, $session, $device)->either(
            granted: function (AGrant $grant) use ($stack, $for): WhatTheGrantCameTo {
                $this->kept->keepTheGrant($stack->id(), $for, $grant);

                return WhatTheGrantCameTo::granted($grant);
            },
            refused: static fn(Obstacle $why): WhatTheGrantCameTo => WhatTheGrantCameTo::refused($why),
        );
    }

    /** This member, on this device. */
    private function for(Whose $whose): TheGrantIsFor
    {
        return TheGrantIsFor::of($whose, $this->device->thisDevice());
    }
}
