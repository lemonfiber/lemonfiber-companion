<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;

/**
 * What asking a stack what the house wants produced, flattened for a template.
 *
 * The sibling of {@see WhatTheStackTurnedOutToBe} and written the same way: a
 * screen folds the answer once and the template reads fields, because Blade has
 * no `either()` and cannot be given one.
 *
 * **Three states, and the empty list is one of them.** A household that has
 * asked for nothing is the ordinary state of a quiet week; a session that has
 * ended is `N1-R44`'s screen; an obstacle is `N1-R10`'s. Folding the first two
 * together would have a signed-out phone say *nobody has asked for anything*,
 * which is the collapse {@see \Modules\Kernel\Api\WhatWasWanted} refuses one
 * layer up and this one must not rebuild.
 */
final readonly class WhatTheHouseholdTurnedOutToWant
{
    /**
     * @param bool                    $isSignedIn whether this device still holds a session for the stack
     * @param string                  $met        the key for what stood in the way, or empty where nothing did
     * @param string                  $remedy     the key for what to do about it, or empty where nothing did
     * @param list<WhatOneRequestSays> $requests every request the house has made, in the stack's order
     * @param int                      $waiting  how many of them want a decision (`N2-R11`)
     */
    private function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public array $requests,
        public int $waiting,
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
        return new self(isSignedIn: false, met: '', remedy: '', requests: [], waiting: 0);
    }

    /** The stack answered, and this is what the house has asked for. */
    public static function these(Requested $wanted): self
    {
        $rows = [];

        foreach ($wanted as $one) {
            $rows[] = WhatOneRequestSays::in($one);
        }

        // The count comes off the collection rather than off the rows, so the
        // line between "waiting" and "not" is drawn once — by `Waiting`, where
        // it belongs — and a fold that dropped a field could never quietly
        // change what this screen says is waiting.
        return new self(isSignedIn: true, met: '', remedy: '', requests: $rows, waiting: $wanted->waiting());
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
        return new self(isSignedIn: true, met: $why->said(), remedy: $why->remedy(), requests: [], waiting: 0);
    }
}
