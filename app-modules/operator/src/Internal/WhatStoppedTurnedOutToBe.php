<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stalled;

/**
 * What asking a stack what has stopped produced, flattened for a template.
 *
 * The sibling of {@see WhatTheHouseholdTurnedOutToWant} and written the same
 * way: a screen folds the answer once and the template reads fields, because
 * Blade has no `either()` and cannot be given one.
 *
 * **Three states, and the empty listing is one of them.** A stack with nothing
 * stuck is the answer an operator wants; a session that has ended is `N1-R44`'s
 * screen; an obstacle is `N1-R10`'s. Folding the first two together would have
 * a signed-out phone report a house where everything is arriving normally,
 * which is the collapse {@see \Modules\Kernel\Api\WhatIsStuck} refuses one
 * layer up and this one must not rebuild.
 *
 * **How much is shown is carried in every state.** A screen that could reach a
 * listing without it is a screen that can claim to be complete by accident, and
 * the states that have no listing carry the key that says so — so the template
 * renders one line rather than branching on whether the field is there.
 */
final readonly class WhatStoppedTurnedOutToBe
{
    /**
     * @param bool                        $isSignedIn whether this device still holds a session for the stack
     * @param string                      $met        the key for what stood in the way, or empty where nothing did
     * @param string                      $remedy     the key for what to do about it, or empty where nothing did
     * @param list<WhatOneStalledItemSays> $stalled   everything that stopped, in the stack's order
     * @param string                      $shownSaid  the key for how much of the listing this is
     */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public array $stalled,
        public string $shownSaid,
    ) {}

    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. `N1-R44`'s screen is where this goes, and the empty keys are what
     * the template branches on.
     */
    public static function signedOut(): self
    {
        return new self(isSignedIn: false, met: '', remedy: '', stalled: [], shownSaid: '');
    }

    /** The stack answered, and this is what has stopped. */
    public static function these(Stalled $stalled): self
    {
        $rows = [];

        foreach ($stalled as $one) {
            $rows[] = WhatOneStalledItemSays::in($one);
        }

        // The key comes off the listing rather than off the rows, so how much
        // is shown is decided where the stack said it and a fold that dropped a
        // row could never quietly turn a partial listing into a complete one.
        return new self(
            isSignedIn: true,
            met: '',
            remedy: '',
            stalled: $rows,
            shownSaid: $stalled->howMuchIsShown()->saidOnTheScreen(),
        );
    }

    /**
     * It did not, and this is what the operator met.
     *
     * The keys come off {@see Obstacle}, which owns them — so an obstacle
     * gaining a seventh case needs no edit here and cannot be given a sentence
     * here that disagrees with the one another screen shows.
     */
    public static function met(Obstacle $why): self
    {
        return new self(isSignedIn: true, met: $why->said(), remedy: $why->remedy(), stalled: [], shownSaid: '');
    }
}
