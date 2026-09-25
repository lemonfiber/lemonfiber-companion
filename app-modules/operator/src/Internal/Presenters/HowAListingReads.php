<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;

/**
 * What asking a stack what it is running comes to, as fields a template reads.
 *
 * **The forms are carried whether or not anything in them is running.** A form
 * with everything stopped is the one an operator came here to start, and a
 * listing assembled from the rows would not have it.
 *
 * **Whether anything is settling is decided over the rows, once.** The screen's
 * cadence reads it and the template states it, and those two have to agree:
 * a screen saying *this is starting* while the poll had stopped would leave
 * somebody watching a sentence that will never change.
 */
final readonly class HowAListingReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. The obstacle screen is where this goes, and the empty keys are what
     * the template branches on.
     */
    public function signedOut(): WhatThisStackRunsTurnedOutToBe
    {
        return new WhatThisStackRunsTurnedOutToBe(
            went: HowTheReadingWent::theSessionEnded(),
            services: [],
            forms: [],
            overall: '',
            isSettling: false,
            disturbs: null,
            active: [],
            leftOut: [],
        );
    }

    /** The stack answered, and this is what it is running. */
    public function these(Daemons $daemons): WhatThisStackRunsTurnedOutToBe
    {
        $rows = [];
        $settling = false;

        foreach ($daemons as $daemon) {
            // A service the forms left out is filtered, and the stack also
            // lists it among the services as absent. Drawn there, it reads as
            // a service that failed; it is drawn once, with why, below.
            if ($daemons->leftOut()->include($daemon->id())) {
                continue;
            }

            $row = new HowAServiceReads()->in($daemon);
            $rows[] = $row;
            $settling = $settling || $row->isSettling;
        }

        $forms = [];

        foreach ($daemons->forms() as $form) {
            $forms[] = $form->named();
        }

        // The overall comes off the listing rather than off the rows, so what
        // the stack amounts to is decided where the stack said it — a fold that
        // dropped a row could never quietly turn a degraded machine into a
        // healthy one.
        return new WhatThisStackRunsTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            services: $rows,
            forms: $forms,
            overall: $daemons->running()->saidOnTheScreen(),
            isSettling: $settling,
            disturbs: $daemons->disturbs(),
            active: new HowWhatWasLeftOutReads()->forms($daemons->active()),
            leftOut: new HowWhatWasLeftOutReads()->of($daemons->leftOut()),
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
     * signed-out app at the next refused call, and that nothing already loaded
     * goes on being rendered — which matters more here than on a listing
     * nobody acts from: what is already loaded on this screen is six buttons
     * that change somebody's machine.
     * {@see Obstacle::meansWeAreSignedOut()} draws the line once, so this fold
     * and every one beside it cannot come to disagree about it.
     */
    public function met(Obstacle $why): WhatThisStackRunsTurnedOutToBe
    {
        return new WhatThisStackRunsTurnedOutToBe(
            went: HowTheReadingWent::somethingStopped($why),
            services: [],
            forms: [],
            overall: '',
            isSettling: false,
            disturbs: null,
            active: [],
            leftOut: [],
        );
    }
}
