<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ThisCopyOfLemonfiber;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\ThisCopyTurnedOutToBe;

/**
 * What asking a stack about its running copy of lemonfiber produces, as the fields a screen draws.
 *
 * `F2`: data in, view model out. The command and the reason there is none
 * come out of one fold, so a screen cannot draw both.
 */
final readonly class HowThisCopyReads
{
    /** This device no longer holds a session for that stack. */
    public function signedOut(): ThisCopyTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::theSessionEnded());
    }

    /** The stack answered, and this is its running copy. */
    public function this(ThisCopyOfLemonfiber $copy): ThisCopyTurnedOutToBe
    {
        $command = $copy->updatedBy()->either(
            byRunning: AsText::of(...),
            instead: static fn(): AsText => AsText::nothing(),
            notSaid: static fn(): AsText => AsText::nothing(),
        );
        $instead = $copy->updatedBy()->either(
            byRunning: static fn(): AsText => AsText::nothing(),
            instead: AsText::of(...),
            notSaid: static fn(): AsText => AsText::nothing(),
        );

        return new ThisCopyTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            running: $copy->running(),
            installedSaid: $copy->gotThere()->installed()->saidOnTheScreen(),
            owner: $copy->gotThere()->owner(),
            standsSaid: $copy->stands()->saidOnTheScreen(),
            offered: $copy->released()->version(),
            changed: $copy->released()->changed(),
            untold: $copy->untold(),
            command: $command->said,
            instead: $instead->said,
            carries: $copy->brings()->carries(),
            afterwards: $copy->brings()->afterwards(),
        );
    }

    /** It did not, and this is what the operator met. */
    public function met(Obstacle $why): ThisCopyTurnedOutToBe
    {
        return $this->nothingFrom(HowTheReadingWent::somethingStopped($why));
    }

    /** An answer with nothing in it, for a reading that did not come back. */
    private function nothingFrom(HowTheReadingWent $went): ThisCopyTurnedOutToBe
    {
        return new ThisCopyTurnedOutToBe(
            went: $went,
            running: '',
            installedSaid: '',
            owner: '',
            standsSaid: '',
            offered: '',
            changed: '',
            untold: '',
            command: '',
            instead: '',
            carries: '',
            afterwards: '',
        );
    }
}
