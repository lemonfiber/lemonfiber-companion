<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\Upkeep;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatTheStackIsOn;
use Modules\Operator\Internal\ViewModels\WhatTheUpkeepTurnedOutToBe;

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
     * asking. This is told apart from a credential that was refused.
     */
    public function signedOut(): WhatTheUpkeepTurnedOutToBe
    {
        return $this->nothingRead(HowTheReadingWent::theSessionEnded());
    }

    /** Where the stack said it stands. */
    public function standing(Upkeep $upkeep): WhatTheUpkeepTurnedOutToBe
    {
        $history = [];

        foreach ($upkeep->history() as $release) {
            $history[] = new HowAReleaseReads()->of($release);
        }

        $on = $upkeep->inUse(
            named: static fn(Release $inUse): WhatTheStackIsOn => new HowTheVersionInUseReads()->of($inUse),
            unstated: static fn(): WhatTheStackIsOn => new HowTheVersionInUseReads()->notNamed(),
        );

        return new WhatTheUpkeepTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            pinsSaid: $upkeep->againstThePins()->saidOnTheScreen(),
            running: $on->version,
            runningWasWithdrawn: $upkeep->runningAWithdrawnRelease(),
            inUse: $on->notes,
            history: $history,
            // Asked of the reading rather than worked out from the history.
            // Releases are listed whether or not a service would move, so a
            // screen counting them would offer an update to a stack that is
            // on every pin.
            offer: $upkeep->hasSomethingToOffer() ? TakingAnUpdate::offeredBy($upkeep) : null,
        );
    }

    /**
     * Something stood in the way of asking.
     *
     * An obstacle that means the session has ended renders the signed-out
     * screen rather than an obstacle, which is the distinction: *your session
     * ended, sign in again* and *the stack refused that credential* send an
     * operator to two different places, and the one that offers a sign-in is
     * the one that is any use.
     */
    public function met(Obstacle $why): WhatTheUpkeepTurnedOutToBe
    {
        return $this->nothingRead(HowTheReadingWent::somethingStopped($why));
    }

    /** A reading that did not happen, which says nothing about the stack. */
    private function nothingRead(HowTheReadingWent $went): WhatTheUpkeepTurnedOutToBe
    {
        return new WhatTheUpkeepTurnedOutToBe(
            went: $went,
            pinsSaid: '',
            running: '',
            runningWasWithdrawn: false,
            inUse: null,
            history: [],
            offer: null,
        );
    }
}
