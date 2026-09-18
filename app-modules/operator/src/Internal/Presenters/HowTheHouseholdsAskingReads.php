<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatTheHouseholdTurnedOutToWant;

/**
 * What asking a stack what the house wants produces, as the fields a screen draws.
 *
 * The sibling of {@see HowAStallReads}: three answers to one asking, none of
 * them reached for, and the rows built one at a time by
 * {@see HowARequestReads}.
 */
final readonly class HowTheHouseholdsAskingReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. The obstacle screen is where this goes, and the empty keys are what
     * the template branches on.
     */
    public function signedOut(): WhatTheHouseholdTurnedOutToWant
    {
        return new WhatTheHouseholdTurnedOutToWant(
            went: HowTheReadingWent::theSessionEnded(),
            requests: [],
            waiting: 0,
        );
    }

    /** The stack answered, and this is what the house has asked for. */
    public function these(Requested $wanted): WhatTheHouseholdTurnedOutToWant
    {
        $rows = [];

        foreach ($wanted as $one) {
            $rows[] = new HowARequestReads()->in($one);
        }

        // The count comes off the collection rather than off the rows, so the
        // line between "waiting" and "not" is drawn once — by `Waiting`, where
        // it belongs — and a fold that dropped a field could never quietly
        // change what this screen says is waiting.
        return new WhatTheHouseholdTurnedOutToWant(
            went: HowTheReadingWent::itCameBack(),
            requests: $rows,
            waiting: $wanted->waiting(),
        );
    }

    /**
     * It did not, and this is what the operator met.
     *
     * The keys come off {@see Obstacle}, which owns them — so an obstacle
     * gaining a seventh case needs no edit here and cannot be given a sentence
     * here that disagrees with the one another screen shows.
     *
     * **A credential the stack refused is a signed-out app, not an obstacle.**
     * An identity removed from the household results in a
     * signed-out app at the next refused call and that nothing already loaded
     * goes on being rendered. {@see Obstacle::meansWeAreSignedOut()} draws that
     * line once, so this fold and every one beside it cannot come to disagree
     * about whether somebody is signed in.
     */
    public function met(Obstacle $why): WhatTheHouseholdTurnedOutToWant
    {
        return new WhatTheHouseholdTurnedOutToWant(
            went: HowTheReadingWent::somethingStopped($why),
            requests: [],
            waiting: 0,
        );
    }
}
