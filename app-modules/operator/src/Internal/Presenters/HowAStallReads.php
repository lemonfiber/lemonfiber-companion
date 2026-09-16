<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stalled;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatStoppedTurnedOutToBe;

/**
 * What asking a stack what has stopped produces, as the fields a screen draws.
 *
 * `F2` — data in, view model out. A listing is a value and an obstacle is a
 * value, so what the screen shows is stated in a test rather than arranged
 * behind a port.
 */
final readonly class HowAStallReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. `N1-R44`'s screen is where this goes, and the empty keys are what
     * the template branches on.
     */
    public function signedOut(): WhatStoppedTurnedOutToBe
    {
        return new WhatStoppedTurnedOutToBe(
            went: HowTheReadingWent::theSessionEnded(),
            stalled: [],
            shownSaid: '',
        );
    }

    /** The stack answered, and this is what has stopped. */
    public function these(Stalled $stalled): WhatStoppedTurnedOutToBe
    {
        $rows = [];

        foreach ($stalled as $one) {
            $rows[] = new HowAStalledItemReads()->in($one);
        }

        // The key comes off the listing rather than off the rows, so how much
        // is shown is decided where the stack said it and a fold that dropped a
        // row could never quietly turn a partial listing into a complete one.
        return new WhatStoppedTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
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
     *
     * **A credential the stack refused is a signed-out app, not an obstacle.**
     * `N3-R13` says an identity removed from the household results in a
     * signed-out app at the next refused call and that nothing already loaded
     * goes on being rendered. {@see Obstacle::meansWeAreSignedOut()} draws that
     * line once, so this fold and every one beside it cannot come to disagree
     * about whether somebody is signed in.
     */
    public function met(Obstacle $why): WhatStoppedTurnedOutToBe
    {
        return new WhatStoppedTurnedOutToBe(
            went: HowTheReadingWent::somethingStopped($why),
            stalled: [],
            shownSaid: '',
        );
    }
}
