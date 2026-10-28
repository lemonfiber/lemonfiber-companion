<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Report;
use Modules\Operator\Internal\ViewModels\AWarningAsRaised;
use Modules\Operator\Internal\ViewModels\TheAcceptanceTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/**
 * Where accepting a warning has got to, as the template draws it.
 */
final readonly class HowTheAcceptanceReads
{
    public function notAsked(): TheAcceptanceTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::notAsked());
    }

    public function signedOut(): TheAcceptanceTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::signedOut());
    }

    public function running(): TheAcceptanceTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::running());
    }

    public function ended(): TheAcceptanceTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::ended());
    }

    public function refused(ARefusalInItsWords $why): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: new HowARefusalReads()->inItsWords($why),
            answered: false,
            accepted: false,
        );
    }

    public function met(Obstacle $why): TheAcceptanceTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::met($why));
    }

    /** What the report the stack answered with says of the check accepted. */
    public function answered(Report $report, string $check): TheAcceptanceTurnedOutToBe
    {
        $raised = new HowAWarningReads()->in($report, $check)->raised;

        return new TheAcceptanceTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: null,
            answered: true,
            accepted: $raised instanceof AWarningAsRaised && $raised->check === null,
        );
    }

    private function without(WhereTheWorkStands $stands): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(
            stands: $stands,
            refused: null,
            answered: false,
            accepted: false,
        );
    }
}
