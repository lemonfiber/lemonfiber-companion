<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\VersionInUse;
use Modules\Operator\Internal\ViewModels\WhatTheStackIsOn;
use Modules\Operator\Internal\ViewModels\WhatTheUpkeepTurnedOutToBe;
use Modules\Updates\Api\Queries\NotArrivedFirst;
use Modules\Updates\Api\Queries\WorthNoticing;

/**
 * Folding what a stack says about being up to date into the fields a screen draws.
 *
 * The sibling of {@see HowAStallReads} and written the same way: a screen folds
 * the answer once and the template reads fields.
 */
final readonly class HowUpkeepReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. `N1-R46` has this told apart from a credential that was refused.
     */
    public function signedOut(): WhatTheUpkeepTurnedOutToBe
    {
        return new WhatTheUpkeepTurnedOutToBe(
            isSignedIn: false,
            met: '',
            remedy: '',
            howSaid: '',
            running: '',
            runningWasWithdrawn: false,
            waiting: [],
            changing: Services::none(),
            applied: [],
            didNotArrive: 0,
            anythingUnanswered: false,
            canTakeOne: false,
            anyWorthNoticing: false,
        );
    }

    /** Where the stack said it stands. */
    public function standing(Upkeep $upkeep): WhatTheUpkeepTurnedOutToBe
    {
        $waiting = [];

        foreach ($upkeep->waiting() as $release) {
            $waiting[] = new HowAReleaseReads()->of($release);
        }

        $applied = [];

        // Ordered before folding, so the rows reach the template in the order
        // they are read in. `NotArrivedFirst` puts every service that is not
        // where the operator wanted it above the ones that are, and ranks the
        // three ways of not arriving against each other not at all — which of
        // a network, a service and a machine matters most is not a judgement
        // this app is in a position to make.
        foreach (new NotArrivedFirst()->over($upkeep->howItWent()) as $took) {
            $applied[] = new HowATakenUpdateReads()->of($took);
        }

        return new WhatTheUpkeepTurnedOutToBe(
            isSignedIn: true,
            met: '',
            remedy: '',
            howSaid: $upkeep->how()->saidOnTheScreen(),
            running: $upkeep->inUse(
                named: static fn(VersionInUse $inUse): WhatTheStackIsOn
                    => new HowTheVersionInUseReads()->of($inUse),
                unstated: static fn(): WhatTheStackIsOn => new HowTheVersionInUseReads()->notNamed(),
            )->version,
            runningWasWithdrawn: $upkeep->runningAWithdrawnRelease(),
            waiting: $waiting,
            changing: $upkeep->changing(),
            applied: $applied,
            didNotArrive: $upkeep->howItWent()->thatDidNotArrive()->count(),
            anythingUnanswered: $upkeep->howItWent()->anythingUnanswered(),
            // `N2-R20`, asked of the reading rather than worked out from the
            // list below. A stack that says it is current is not asked further,
            // and a screen counting rows would offer an update to one that
            // listed releases while reporting itself up to date.
            canTakeOne: $upkeep->hasSomethingToOffer(),
            // `N2-R16`'s distinction, asked rather than counted: a screen
            // deciding whether tonight is worth an evening should not have to
            // build a list to find out that it is empty.
            anyWorthNoticing: ! new WorthNoticing()->over($upkeep->waiting())->isEmpty(),
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
    public function met(Obstacle $why): WhatTheUpkeepTurnedOutToBe
    {
        if ($why->meansWeAreSignedOut()) {
            return $this->signedOut();
        }

        return new WhatTheUpkeepTurnedOutToBe(
            isSignedIn: true,
            met: $why->said(),
            remedy: $why->remedy(),
            howSaid: '',
            running: '',
            runningWasWithdrawn: false,
            waiting: [],
            changing: Services::none(),
            applied: [],
            didNotArrive: 0,
            anythingUnanswered: false,
            canTakeOne: false,
            anyWorthNoticing: false,
        );
    }
}
