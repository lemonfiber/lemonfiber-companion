<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Upkeep;
use Modules\Operator\Internal\ViewModels\HowTheLastUpdateWent;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;
use Modules\Updates\Api\Queries\NotArrivedFirst;

/**
 * What became of an update taken, as the template draws it under the reading.
 *
 * One method per state of following it, each saying only its own state, so a
 * template never reads a list of services off an update still running.
 */
final readonly class HowTheLastUpdateReads
{
    /** Nothing was taken from this screen, so there is nothing to follow. */
    public function notTaken(): HowTheLastUpdateWent
    {
        return $this->following(WhereTheWorkStands::answered(), wasTaken: false);
    }

    /** This device no longer holds a session for the stack the update was taken on. */
    public function signedOut(): HowTheLastUpdateWent
    {
        return $this->following(WhereTheWorkStands::signedOut());
    }

    /** The stack is still carrying it out. */
    public function running(): HowTheLastUpdateWent
    {
        return $this->following(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for it any more, which is not the same as it failing. */
    public function ended(): HowTheLastUpdateWent
    {
        return $this->following(WhereTheWorkStands::ended());
    }

    /** Taking it, or asking after it, met this instead. */
    public function met(Obstacle $why): HowTheLastUpdateWent
    {
        return $this->following(WhereTheWorkStands::met($why));
    }

    /**
     * The update's own report, service by service.
     *
     * Ordered before folding, so every service that is not where the operator
     * wanted it comes first; {@see NotArrivedFirst} says why the three ways of
     * not arriving are not ranked against each other.
     */
    public function done(Upkeep $report): HowTheLastUpdateWent
    {
        $applied = [];

        foreach (new NotArrivedFirst()->over($report->howItWent()) as $took) {
            $applied[] = new HowATakenUpdateReads()->of($took);
        }

        return new HowTheLastUpdateWent(
            stands: WhereTheWorkStands::answered(),
            wasTaken: true,
            applied: $applied,
            didNotArrive: $report->howItWent()->thatDidNotArrive()->count(),
            anythingUnanswered: $report->howItWent()->anythingUnanswered(),
            moved: new HowAMoveInPlaceReads()->tried($report->whatBecameOfTheMoves()),
        );
    }

    /** A state with no services to list. */
    private function following(
        WhereTheWorkStands $stands,
        bool $wasTaken = true,
    ): HowTheLastUpdateWent {
        return new HowTheLastUpdateWent(
            stands: $stands,
            wasTaken: $wasTaken,
            applied: [],
            didNotArrive: 0,
            anythingUnanswered: false,
            moved: [],
        );
    }
}
