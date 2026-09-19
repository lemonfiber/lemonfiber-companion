<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeAbleToWatch;
use Modules\Household\Internal\ViewModels\WhatOneHoldingSays;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Shelf;

/**
 * A member's shelf, flattened into what a template draws.
 *
 * Pure, like every presenter here: it reads what it was handed and reaches
 * nothing. What is on the shelf was decided by the core before this was called,
 * and nothing in here filters, sorts or hides a row — a presenter that dropped
 * one would be a second place a member's library is decided.
 */
final readonly class HowAShelfReads
{
    /** The core answered, and this is what it says they may watch. */
    public function these(Shelf $shelf): WhatAMemberTurnedOutToBeAbleToWatch
    {
        $rows = [];

        foreach ($shelf as $holding) {
            // Folded rather than tested, so the undated arm is one the type
            // insists on: a holding the core could not date is shown undated,
            // and a year invented here would be a fact about somebody's
            // library that nobody claimed.
            $rows[] = $holding->year()->either(
                dated: static fn(int $year): WhatOneHoldingSays => new WhatOneHoldingSays(
                    titled: $holding->titled(),
                    medium: $holding->medium()->saidOnTheScreen(),
                    year: (string) $year,
                ),
                unstated: static fn(): WhatOneHoldingSays => new WhatOneHoldingSays(
                    titled: $holding->titled(),
                    medium: $holding->medium()->saidOnTheScreen(),
                    year: '',
                ),
            );
        }

        return WhatAMemberTurnedOutToBeAbleToWatch::these($rows);
    }

    /** The core answered and the library was not its to hand over. */
    public function outOfReach(Sentences $said): WhatAMemberTurnedOutToBeAbleToWatch
    {
        $reasons = [];

        foreach ($said as $sentence) {
            $reasons[] = $sentence->shown();
        }

        return WhatAMemberTurnedOutToBeAbleToWatch::outOfReach($reasons);
    }

    /**
     * Something stood in the way, and this is what the member met.
     *
     * The keys come off {@see Obstacle}, so an obstacle gaining another case
     * needs no edit here and cannot be given a sentence that disagrees with
     * the screen beside this one.
     */
    public function met(Obstacle $why): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return WhatAMemberTurnedOutToBeAbleToWatch::somethingStopped(
            $why->said(),
            $why->remedy(),
            $why->meansWeAreSignedOut(),
        );
    }

    /** This device holds no session for that stack, so nothing was asked. */
    public function signedOut(): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return WhatAMemberTurnedOutToBeAbleToWatch::theSessionEnded();
    }
}
