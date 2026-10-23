<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;

/** Where running a check that disturbs has got to, as the fields a screen draws: one method per state. */
final readonly class HowTheRunReads
{
    public function notAsked(): TheRunTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack());
    }

    public function signedOut(): TheRunTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    public function running(): TheRunTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    public function ended(): TheRunTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    public function refused(ARefusalInItsWords $why): TheRunTurnedOutToBe
    {
        return new TheRunTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            refused: new HowARefusalReads()->inItsWords($why),
            answered: false,
        );
    }

    public function met(Obstacle $why): TheRunTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why));
    }

    /** The run finished, and its report is what the finding is drawn from now. */
    public function answered(): TheRunTurnedOutToBe
    {
        return new TheRunTurnedOutToBe(went: HowTheReadingWent::itCameBack(), isWorking: false, hasEnded: false, refused: null, answered: true);
    }

    private function without(HowTheReadingWent $went, bool $isWorking = false, bool $hasEnded = false): TheRunTurnedOutToBe
    {
        return new TheRunTurnedOutToBe(went: $went, isWorking: $isWorking, hasEnded: $hasEnded, refused: null, answered: false);
    }
}
