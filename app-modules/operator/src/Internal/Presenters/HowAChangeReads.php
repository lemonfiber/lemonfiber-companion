<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;

/**
 * A proposal, turned into what the screen draws.
 *
 * Four named ways it ends, so a screen cannot reach a fifth by leaving a
 * branch out. {@see nothingOpen()} is the one that is not about the stack at
 * all: it is what a tap on a control the screen is not drawing comes to, and
 * it exists so that case has an answer rather than an exception.
 */
final readonly class HowAChangeReads
{
    public function stands(WhereTheChangeStands $stands): WhatAChangeTurnedOutToBe
    {
        return $stands->change->from->either(
            shown: fn(string $held): WhatAChangeTurnedOutToBe
                => $this->said($stands, $held, holdsNothingYet: false),
            nothingYet: fn(): WhatAChangeTurnedOutToBe
                => $this->said($stands, '', holdsNothingYet: true),
        );
    }

    public function met(Obstacle $why): WhatAChangeTurnedOutToBe
    {
        return $this->nothing(HowTheReadingWent::somethingStopped($why));
    }

    public function signedOut(): WhatAChangeTurnedOutToBe
    {
        return $this->nothing(HowTheReadingWent::theSessionEnded());
    }

    /**
     * A tap that reached here with no setting open.
     *
     * Reported as a reading that came back with nothing to say rather than as
     * an obstacle: nothing went wrong with the stack, and telling an operator
     * their machine could not be reached would send them looking at the wrong
     * thing.
     */
    public function nothingOpen(): WhatAChangeTurnedOutToBe
    {
        return $this->nothing(HowTheReadingWent::itCameBack());
    }

    private function said(
        WhereTheChangeStands $stands,
        string $held,
        bool $holdsNothingYet,
    ): WhatAChangeTurnedOutToBe {
        return new WhatAChangeTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            key: $stands->change->key,
            fromSaid: $held,
            toSaid: $stands->change->to,
            holdsNothingYet: $holdsNothingYet,
            costSaid: $stands->change->cost->saidOnTheScreen(),
            stanceSaid: $stands->stance->saidOnTheScreen(),
            mustBeAgreedFirst: $stands->change->cost->mustBeAgreedFirst(),
            holdsWhatWasAsked: $stands->stance->holdsWhatWasAsked(),
            wroteSomething: $stands->stance->wroteSomething(),
            refusalSaid: $stands->why(),
        );
    }

    private function nothing(HowTheReadingWent $went): WhatAChangeTurnedOutToBe
    {
        return new WhatAChangeTurnedOutToBe(
            went: $went,
            key: '',
            fromSaid: '',
            toSaid: '',
            holdsNothingYet: false,
            costSaid: '',
            stanceSaid: '',
            mustBeAgreedFirst: false,
            holdsWhatWasAsked: false,
            wroteSomething: false,
            refusalSaid: '',
        );
    }
}
