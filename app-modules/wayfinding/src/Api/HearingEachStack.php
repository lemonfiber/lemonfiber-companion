<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Whose;

/**
 * Each stack's one line, heard on its own subscription, one wake at a time.
 *
 * The list of stacks and the list the top bar's name opens both say how each
 * stack stands, and both listen for it the same way, so it is written once.
 * Every word a subscription says goes into
 * {@see \Modules\Kernel\Api\Standings}, which is what their rows read.
 *
 * **Only a stack this device is signed into as its operator is listened to.** A
 * stack with no session has nothing to listen with, and a member's session is
 * not the one the stack's summary is for.
 *
 * Every other rule is the stack's own screen's, per stack: it is held only
 * while somebody can see it, a silence past the contract's bound breaks it, a
 * broken one is opened again on
 * {@see \Modules\Kernel\Api\HowOftenAScreenLooks::AfterABreak}'s cadence, and a
 * session the stack refuses is let go of through
 * {@see LetsGoOfARefusedSession}. Letting go of a quiet one lets go of every
 * stream this holds, because {@see \Modules\Kernel\Api\Hearing::letGo()} takes
 * no stack; the others open again on their next wake.
 *
 * **Its streams are the ones its {@see WhatItListensWith} holds.** The list of
 * stacks hands it the list's own. The list the top bar's name opens has one
 * of its own, so letting go of that one lets go of nothing the screen under it
 * holds.
 */
final readonly class HearingEachStack
{
    use LetsGoOfARefusedSession;

    public function __construct(private WhatItListensWith $with, private SecureStorage $storage) {}

    /**
     * What each subscription holds after this wake, or every one let go of where nobody can see them.
     *
     * Opens a stack's subscription where it is not open and its break has been
     * waited out. Taking sends nothing to the stack.
     *
     */
    public function after(WhatEachStackSaidSoFar $heard, Stack ...$stacks): WhatEachStackSaidSoFar
    {
        if (! $this->with->capture->isInFront()) {
            return $this->letGo($heard);
        }

        $now = $this->with->clock->now();

        foreach ($stacks as $stack) {
            $heard = $this->storage->resume($stack->id())->either(
                held: fn(Session $session, Whose $whose): WhatEachStackSaidSoFar => $whose->either(
                    operator: fn(): WhatEachStackSaidSoFar => $heard->with(
                        $stack->id(),
                        $this->listenedTo($heard->from($stack->id()), $stack, $session, $now),
                    ),
                    member: static fn(): WhatEachStackSaidSoFar => $heard,
                ),
                notHeld: static fn(): WhatEachStackSaidSoFar => $heard,
            );
        }

        return $heard;
    }

    /** Every subscription let go of, and what each held kept as no longer current. */
    public function letGo(WhatEachStackSaidSoFar $heard): WhatEachStackSaidSoFar
    {
        $this->with->hearing->letGo();

        return $heard->wentAway();
    }

    /** What one stack's subscription holds after this wake. */
    private function listenedTo(WhatWasHeardSoFar $held, Stack $stack, Session $session, Instant $now): WhatWasHeardSoFar
    {
        if (! $held->mayListen($now)) {
            return $held;
        }

        $hearing = $this->with->hearing;
        $held = $held->after($this->with->kept($hearing->howItIs($stack, $session), $stack, $now), $now);

        if ($held->hasGoneQuiet($now)) {
            $held = $held->after($hearing->letGo(), $now);
        }

        return $held->stoppedBy(
            nothing: static fn(): WhatWasHeardSoFar => $held,
            met: function (Obstacle $why) use ($held, $stack): WhatWasHeardSoFar {
                $this->letGoOfTheSession($why, $stack);

                return $held;
            },
        );
    }
}
