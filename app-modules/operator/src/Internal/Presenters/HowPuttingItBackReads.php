<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhereTheDataGoes;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\ARelocationAsShown;
use Modules\Operator\Internal\ViewModels\HowPuttingItBackWent;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatPuttingItBackWouldShow;

/**
 * Putting a copy back, as the template draws it: the listing first, and what
 * became of the yes after.
 *
 * Two families of method for two values, so a listing of what would happen
 * and a report of what did never share a model.
 */
final readonly class HowPuttingItBackReads
{
    /** What the stack listed about putting the copy back. */
    public function listing(WhatPuttingItBackWouldDo $listing): WhatPuttingItBackWouldShow
    {
        return new WhatPuttingItBackWouldShow(
            went: HowTheReadingWent::itCameBack(),
            namesACopy: true,
            scope: new HowAScopeReads()->of($listing->scope()),
            takenBy: $listing->takenBy(),
            takenAt: $listing->takenAt(),
            contents: [...$listing->contents()],
            isOlder: $listing->isOlder(),
            relocation: $this->relocation($listing->whereTheDataGoes()),
        );
    }

    /** The stack would not list the copy, and this is what stood in the way. */
    public function notListed(Obstacle $why): WhatPuttingItBackWouldShow
    {
        return $this->nothingListed(HowTheReadingWent::somethingStopped($why));
    }

    /** This device no longer holds a session for the stack, so nothing was asked. */
    public function notAsked(): WhatPuttingItBackWouldShow
    {
        return $this->nothingListed(HowTheReadingWent::theSessionEnded());
    }

    /** The screen was opened naming no copy, so there is nothing to ask about. */
    public function namesNoCopy(): WhatPuttingItBackWouldShow
    {
        return $this->nothingListed(HowTheReadingWent::itCameBack(), namesACopy: false);
    }

    /** The stack is still putting it back. */
    public function running(): HowPuttingItBackWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(): HowPuttingItBackWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    /** Putting it back, or asking after it, met this instead. */
    public function met(Obstacle $why): HowPuttingItBackWent
    {
        return $this->following(HowTheReadingWent::somethingStopped($why));
    }

    /** This device no longer holds a session for the stack it was put back on. */
    public function signedOut(): HowPuttingItBackWent
    {
        return $this->following(HowTheReadingWent::theSessionEnded());
    }

    /** The stack's report of what putting it back did. */
    public function done(ACopyPutBack $report): HowPuttingItBackWent
    {
        return new HowPuttingItBackWent(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            scope: new HowAScopeReads()->of($report->scope()),
            takenBy: $report->takenBy(),
            relocation: $this->relocation($report->whereTheDataWent()),
        );
    }

    /** Where the data goes, as both roots, or nothing where it goes back where it came from. */
    private function relocation(WhereTheDataGoes $data): ?ARelocationAsShown
    {
        $shown = $data->either(
            whereItWas: static fn(): AsText => AsText::nothing(),
            elsewhere: static fn(ARelocation $moved): ARelocationAsShown => new ARelocationAsShown(
                was: $moved->was(),
                now: $moved->now(),
            ),
        );

        return $shown instanceof ARelocationAsShown ? $shown : null;
    }

    /** A listing that did not come back, with nothing in it. */
    private function nothingListed(HowTheReadingWent $went, bool $namesACopy = true): WhatPuttingItBackWouldShow
    {
        return new WhatPuttingItBackWouldShow(
            went: $went,
            namesACopy: $namesACopy,
            scope: null,
            takenBy: null,
            takenAt: null,
            contents: [],
            isOlder: false,
            relocation: null,
        );
    }

    /** A state with no report to draw. */
    private function following(
        HowTheReadingWent $went,
        bool $isWorking = false,
        bool $hasEnded = false,
    ): HowPuttingItBackWent {
        return new HowPuttingItBackWent(
            went: $went,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            scope: null,
            takenBy: null,
            relocation: null,
        );
    }
}
