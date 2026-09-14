<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Upkeep;

/**
 * What asking a stack where it stands produced, flattened for a template.
 *
 * The sibling of {@see WhatStoppedTurnedOutToBe} and written the same way: a
 * screen folds the answer once and the template reads fields.
 *
 * **Three states, and *nothing waiting* is one of them.** A stack that is
 * current is the answer an operator wants; a session that has ended is
 * `N1-R44`'s screen; an obstacle is `N1-R10`'s. Folding the first two together
 * would have a signed-out phone report a house that is up to date, which is the
 * collapse {@see \Modules\Kernel\Api\WhatIsCurrent} refuses one layer up.
 *
 * **What the stack said about itself is carried in every state.** A screen that
 * could reach a listing without it is a screen that can claim to be current by
 * accident, so the states with no listing carry the key that says so and the
 * template renders one line rather than branching on whether a field is there.
 */
final readonly class WhatTheUpkeepTurnedOutToBe
{
    /**
     * @param bool                     $isSignedIn whether this device still holds a session for the stack
     * @param string                   $met        the key for what stood in the way, or empty where nothing did
     * @param string                   $remedy     the key for what to do about it, or empty where nothing did
     * @param string                   $howSaid    the key for whether the stack is current, pending or stale
     * @param string                   $running    the version in use, or empty where the stack named none
     * @param bool                     $runningWasWithdrawn whether the version in use has been taken back
     * @param list<WhatOneReleaseSays> $waiting    the releases worth offering, in the stack's order
     */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public string $howSaid,
        public string $running,
        public bool $runningWasWithdrawn,
        public array $waiting,
    ) {}

    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. `N1-R46` has this told apart from a credential that was refused.
     */
    public static function signedOut(): self
    {
        return new self(
            isSignedIn: false,
            met: '',
            remedy: '',
            howSaid: '',
            running: '',
            runningWasWithdrawn: false,
            waiting: [],
        );
    }

    /** Where the stack said it stands. */
    public static function standing(Upkeep $upkeep): self
    {
        $waiting = [];

        foreach ($upkeep->waiting() as $release) {
            $waiting[] = WhatOneReleaseSays::of($release);
        }

        return new self(
            isSignedIn: true,
            met: '',
            remedy: '',
            howSaid: $upkeep->how()->saidOnTheScreen(),
            running: $upkeep->running(
                on: static fn(Release $release): WhatOneReleaseSays => WhatOneReleaseSays::of($release),
                unstated: static fn(): WhatOneReleaseSays => WhatOneReleaseSays::of(Release::called('—', noticeable: false, withdrawn: false)),
            )->version,
            runningWasWithdrawn: $upkeep->runningAWithdrawnRelease(),
            waiting: $waiting,
        );
    }

    /**
     * Something stood in the way of asking (`N1-R10`).
     *
     * An obstacle that means the session has ended renders the signed-out
     * screen rather than an obstacle, which `N1-R46` is about: *your session
     * ended, sign in again* and *the stack refused that credential* send an
     * operator to two different places, and the one that offers a sign-in is
     * the one that is any use.
     */
    public static function met(Obstacle $why): self
    {
        if ($why->meansWeAreSignedOut()) {
            return self::signedOut();
        }

        return new self(
            isSignedIn: true,
            met: $why->said(),
            remedy: $why->remedy(),
            howSaid: '',
            running: '',
            runningWasWithdrawn: false,
            waiting: [],
        );
    }
}
