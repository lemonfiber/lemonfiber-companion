<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheAcceptanceTurnedOutToBe;

/**
 * Where accepting a warning has got to, as the template draws it.
 */
final readonly class HowTheAcceptanceReads
{
    public function notAsked(): TheAcceptanceTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack());
    }

    public function signedOut(): TheAcceptanceTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded());
    }

    public function running(): TheAcceptanceTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), isWorking: true);
    }

    public function ended(): TheAcceptanceTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), hasEnded: true);
    }

    public function refused(ARefusalInItsWords $why): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            refused: new HowARefusalReads()->inItsWords($why),
            answered: false,
            accepted: false,
        );
    }

    public function met(Obstacle $why): TheAcceptanceTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why));
    }

    /** What the report the stack answered with says of the check accepted. */
    public function answered(Report $report, string $check): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            refused: null,
            answered: true,
            accepted: new HowAWarningReads()->in($report, $check)->accepted,
        );
    }

    private function without(HowTheReadingWent $went, bool $isWorking = false, bool $hasEnded = false): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(
            went: $went,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            refused: null,
            answered: false,
            accepted: false,
        );
    }
}
