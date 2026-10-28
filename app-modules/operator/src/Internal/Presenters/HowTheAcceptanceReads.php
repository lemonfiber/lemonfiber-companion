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
        return new TheAcceptanceTurnedOutToBe(WhereTheWorkStands::notAsked());
    }

    public function signedOut(): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(WhereTheWorkStands::signedOut());
    }

    public function running(): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(WhereTheWorkStands::running());
    }

    public function ended(): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(WhereTheWorkStands::ended());
    }

    public function refused(ARefusalInItsWords $why): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: new HowARefusalReads()->inItsWords($why),
        );
    }

    public function met(Obstacle $why): TheAcceptanceTurnedOutToBe
    {
        return new TheAcceptanceTurnedOutToBe(WhereTheWorkStands::met($why));
    }

    /** What the report the stack answered with says of the check accepted. */
    public function answered(Report $report, string $check): TheAcceptanceTurnedOutToBe
    {
        $raised = new HowAWarningReads()->in($report, $check)->raised;

        return new TheAcceptanceTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            accepted: $raised instanceof AWarningAsRaised && $raised->check === null,
        );
    }
}
