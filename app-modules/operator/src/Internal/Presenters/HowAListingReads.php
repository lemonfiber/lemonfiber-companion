<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_unique;
use function array_values;

use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheFormsAsFound;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Services\Api\Queries\WithoutWhatWasLeftOut;

/**
 * What asking a stack what it is running comes to, as fields a template reads.
 *
 * **The forms are folded apart from the listing**, because they are read apart
 * from it, on a frame of their own. A form with everything stopped is the one
 * an operator came here to start, and a listing assembled from the rows would
 * not have it.
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
        return $this->stoppedBy(HowTheReadingWent::theSessionEnded());
    }

    /** The stack answered, and this is what it is running. */
    public function these(Daemons $daemons): WhatThisStackRunsTurnedOutToBe
    {
        return $this->read($daemons, HowTheReadingWent::itCameBack(), AgoAsShown::live(), waits: false);
    }

    /**
     * What the stack was running, as the phone kept it from an earlier listing.
     *
     * Drawn whole, as a listing that came back when it was read, with how long
     * ago that was, beside whatever this frame's asking met, and with every
     * action on it waiting for a fresh one.
     */
    public function kept(Daemons $daemons, Instant $readAt, Instant $now, HowTheReadingWent $askedNow): WhatThisStackRunsTurnedOutToBe
    {
        return $this->read($daemons, $askedNow, AgoAsShown::from(HowLongAgo::since($readAt, $now), $readAt, $now), waits: true);
    }

    /**
     * The forms a kept listing names, as the forms the stack declares.
     *
     * The forms are a reading of their own, kept nowhere, so a listing the
     * phone kept offers the forms it names: those asked for, those that
     * brought a service in, and those that left one out.
     */
    public function formsNamedIn(Daemons $daemons): TheFormsAsFound
    {
        $names = new HowWhatWasLeftOutReads()->forms($daemons->active());

        foreach ($daemons as $daemon) {
            $names = [...$names, ...new HowWhatWasLeftOutReads()->forms($daemon->whatBroughtItIn())];
        }

        foreach ($daemons->leftOut() as $left) {
            $names = [...$names, ...new HowWhatWasLeftOutReads()->forms($left->askedBy())];
        }

        return new TheFormsAsFound(HowTheReadingWent::itCameBack(), array_values(array_unique($names)));
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
        return $this->stoppedBy(HowTheReadingWent::somethingStopped($why));
    }

    /** A listing that did not come back, for the reason given. */
    public function stoppedBy(HowTheReadingWent $went): WhatThisStackRunsTurnedOutToBe
    {
        return new WhatThisStackRunsTurnedOutToBe(
            went: $went,
            services: [],
            overall: '',
            isSettling: false,
            disturbs: null,
            active: [],
            leftOut: [],
            askedNow: $went,
            readAgo: AgoAsShown::live(),
            waitsForTheStack: false,
        );
    }

    /** The stack listed the forms it declares, which may be none. */
    public function forms(Forms $forms): TheFormsAsFound
    {
        $names = [];

        foreach ($forms as $form) {
            $names[] = $form->named();
        }

        return new TheFormsAsFound(HowTheReadingWent::itCameBack(), $names);
    }

    /** It did not, and this is what the operator met. */
    public function formsMet(Obstacle $why): TheFormsAsFound
    {
        return new TheFormsAsFound(HowTheReadingWent::somethingStopped($why), []);
    }

    /** The session ended before the forms were asked. */
    public function formsSignedOut(): TheFormsAsFound
    {
        return new TheFormsAsFound(HowTheReadingWent::theSessionEnded(), []);
    }

    private function read(Daemons $daemons, HowTheReadingWent $askedNow, AgoAsShown $ago, bool $waits): WhatThisStackRunsTurnedOutToBe
    {
        $rows = [];
        $settling = false;

        // A service the forms left out is drawn once, with why, below, and
        // not again among the services as one that failed.
        foreach (new WithoutWhatWasLeftOut()->over($daemons) as $daemon) {
            $row = new HowAServiceReads()->in($daemon, $daemons);
            $rows[] = $row;
            $settling = $settling || $row->isSettling;
        }

        // The overall comes off the listing rather than off the rows, so what
        // the stack amounts to is decided where the stack said it — a fold that
        // dropped a row could never quietly turn a degraded machine into a
        // healthy one. A kept listing becomes nothing else by itself, however
        // its rows stood when it was read, so it is never settling.
        return new WhatThisStackRunsTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            services: $rows,
            overall: $daemons->running()->saidOnTheScreen(),
            isSettling: $settling && ! $waits,
            disturbs: $daemons->disturbs(),
            active: new HowWhatWasLeftOutReads()->forms($daemons->active()),
            leftOut: new HowWhatWasLeftOutReads()->of($daemons->leftOut()),
            askedNow: $askedNow,
            readAgo: $ago,
            waitsForTheStack: $waits,
        );
    }
}
