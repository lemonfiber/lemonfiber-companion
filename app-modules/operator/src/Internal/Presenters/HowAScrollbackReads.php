<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Scrollback;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatTheServiceTurnedOutToSay;

/**
 * What reading one service's tail produces, as the fields a template reads.
 *
 * **What arrived and what is shown are separate numbers**, because a search
 * narrows the second and must not narrow the first. A window of two hundred
 * filtered to twelve is still a window that stopped at two hundred, and a
 * screen that recomputed the claim from the twelve would tell an operator the
 * search covered everything the service ever said — which is the one lie this
 * screen can tell that somebody would act on.
 */
final readonly class HowAScrollbackReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. The obstacle screen is where this goes, and the empty keys are what
     * the template branches on.
     */
    public function signedOut(): WhatTheServiceTurnedOutToSay
    {
        return new WhatTheServiceTurnedOutToSay(
            went: HowTheReadingWent::theSessionEnded(),
            lines: [],
            arrived: 0,
            bound: 0,
            isAWindow: false,
            isSearching: false,
        );
    }

    /** The stack answered, and this is the window, narrowed to what was typed. */
    public function this(Scrollback $scrollback, LookingFor $looking): WhatTheServiceTurnedOutToSay
    {
        $shown = $scrollback->matching($looking);

        $rows = [];

        foreach ($shown as $line) {
            $rows[] = new HowALineReads()->in($line);
        }

        // Every claim about the edge comes off the window rather than off the
        // rows, which is what keeps a search from quietly widening it.
        return new WhatTheServiceTurnedOutToSay(
            went: HowTheReadingWent::itCameBack(),
            lines: $rows,
            arrived: $shown->howManyArrived(),
            bound: $shown->asked()->figure(),
            isAWindow: $shown->isAWindow(),
            isSearching: $shown->lookingFor()->isSearching(),
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
     * about whether somebody is signed in — and a window already fetched is not
     * shown under a sentence about a machine.
     */
    public function met(Obstacle $why): WhatTheServiceTurnedOutToSay
    {
        return new WhatTheServiceTurnedOutToSay(
            went: HowTheReadingWent::somethingStopped($why),
            lines: [],
            arrived: 0,
            bound: 0,
            isAWindow: false,
            isSearching: false,
        );
    }
}
