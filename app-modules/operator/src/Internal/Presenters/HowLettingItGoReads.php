<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\HowBig;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\ViewModels\ASizeAsShown;
use Modules\Operator\Internal\ViewModels\HowLettingItGoWent;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatLettingItGoWouldShow;

/**
 * Stopping seeding one download, as the template draws it: the offer first,
 * and what became of the yes after.
 *
 * Two families of method for two values, so an offer of what it would cost
 * and a report of what happened never share a model. The download on the
 * offer is drawn by {@see HowTheRoomReads}, in the words the account of the
 * disk draws it in.
 */
final readonly class HowLettingItGoReads
{
    /** The stack's offer, with what stopping would cost. */
    public function offering(WhatLettingItGoCosts $offer): WhatLettingItGoWouldShow
    {
        return new WhatLettingItGoWouldShow(
            went: HowTheReadingWent::itCameBack(),
            namesADownload: true,
            isWorking: false,
            hasEnded: false,
            download: new HowTheRoomReads()->download($offer->download()),
            goes: $offer->goes(),
        );
    }

    /** The stack is still working out what it would cost. */
    public function stillWorkingItOut(): WhatLettingItGoWouldShow
    {
        return $this->nothingOffered(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for the asking any more, and asking again starts afresh. */
    public function offerEnded(): WhatLettingItGoWouldShow
    {
        return $this->nothingOffered(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** Asking, or reading what it came to, met this instead. */
    public function notOffered(Obstacle $why): WhatLettingItGoWouldShow
    {
        return $this->nothingOffered(HowTheReadingWent::somethingStopped($why));
    }

    /** This device no longer holds a session for the stack, so nothing was asked. */
    public function notAsked(): WhatLettingItGoWouldShow
    {
        return $this->nothingOffered(HowTheReadingWent::theSessionEnded());
    }

    /** The screen was opened naming no download, so there is nothing to ask about. */
    public function namesNoDownload(): WhatLettingItGoWouldShow
    {
        return $this->nothingOffered(HowTheReadingWent::itCameBack(), namesADownload: false);
    }

    /** The stack is still asking the client. */
    public function running(): HowLettingItGoWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(): HowLettingItGoWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** Stopping, or asking after it, met this instead. */
    public function met(Obstacle $why): HowLettingItGoWent
    {
        return $this->following(HowTheReadingWent::somethingStopped($why));
    }

    /** This device no longer holds a session for the stack it was stopped on. */
    public function signedOut(): HowLettingItGoWent
    {
        return $this->following(HowTheReadingWent::theSessionEnded());
    }

    /** The stack's report of what stopping did, or would have done where it was rehearsed. */
    public function done(ADownloadLetGo $report): HowLettingItGoWent
    {
        $big = HowBig::of($report->bytes());

        return new HowLettingItGoWent(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            wasRehearsed: $report->wasRehearsed() === WhetherItWasRehearsed::Rehearsed,
            name: $report->name(),
            size: new ASizeAsShown(figure: $big->figure, unit: $big->said),
        );
    }

    /** An offer that did not come back, with nothing in it. */
    private function nothingOffered(
        HowTheReadingWent $went,
        bool $namesADownload = true,
        bool $isWorking = false,
        bool $hasEnded = false,
    ): WhatLettingItGoWouldShow {
        return new WhatLettingItGoWouldShow(
            went: $went,
            namesADownload: $namesADownload,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            download: null,
            goes: '',
        );
    }

    /** A state with no report to draw. */
    private function following(
        HowTheReadingWent $went,
        bool $isWorking = false,
        bool $hasEnded = false,
    ): HowLettingItGoWent {
        return new HowLettingItGoWent(
            went: $went,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            wasRehearsed: false,
            name: '',
            size: null,
        );
    }
}
