<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Operator\Internal\ViewModels\TheRunTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/** Where running a check that disturbs has got to, as the fields a screen draws: one method per state. */
final readonly class HowTheRunReads
{
    public function notAsked(): TheRunTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::notAsked());
    }

    public function signedOut(): TheRunTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::signedOut());
    }

    public function running(): TheRunTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::running());
    }

    public function ended(): TheRunTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::ended());
    }

    public function refused(ARefusalInItsWords $why): TheRunTurnedOutToBe
    {
        return new TheRunTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: new HowARefusalReads()->inItsWords($why),
            answered: false,
        );
    }

    public function met(Obstacle $why): TheRunTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::met($why));
    }

    /** The run finished, and its report is what the finding is drawn from now. */
    public function answered(): TheRunTurnedOutToBe
    {
        return new TheRunTurnedOutToBe(stands: WhereTheWorkStands::answered(), refused: null, answered: true);
    }

    private function without(WhereTheWorkStands $stands): TheRunTurnedOutToBe
    {
        return new TheRunTurnedOutToBe(stands: $stands, refused: null, answered: false);
    }
}
