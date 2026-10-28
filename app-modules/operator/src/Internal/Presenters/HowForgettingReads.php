<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function array_map;
use function array_values;

use Modules\Kernel\Api\APathLeft;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheForgetting;
use Modules\Kernel\Api\ThePlacesGone;
use Modules\Operator\Internal\ViewModels\APlaceLeftAsShown;
use Modules\Operator\Internal\ViewModels\TheForgettingTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/**
 * Where removing everything lemonfiber keeps has got to, as the template draws it.
 */
final readonly class HowForgettingReads
{
    public function notAsked(): TheForgettingTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::notAsked());
    }

    public function signedOut(): TheForgettingTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::signedOut());
    }

    public function running(): TheForgettingTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::running());
    }

    public function ended(): TheForgettingTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::ended());
    }

    public function refused(ARefusalInItsWords $why): TheForgettingTurnedOutToBe
    {
        return new TheForgettingTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: new HowARefusalReads()->inItsWords($why),
            wouldGo: null,
            theWayInWent: false,
            isDone: false,
            gone: [],
            left: [],
        );
    }

    /** What stood in the way: before a yes, an obstacle; after one, this app's way in having gone with everything else. */
    public function met(Obstacle $why, bool $agreed): TheForgettingTurnedOutToBe
    {
        return $agreed
            ? $this->without(WhereTheWorkStands::answered(), theWayInWent: true)
            : $this->without(WhereTheWorkStands::met($why));
    }

    /** What the stack answered: the list of what would go, or what went. */
    public function answered(TheForgetting $forgetting): TheForgettingTurnedOutToBe
    {
        return $forgetting->got()->either(
            notAsked: fn(): TheForgettingTurnedOutToBe => $this->without(WhereTheWorkStands::answered()),
            unconfirmed: static fn(): TheForgettingTurnedOutToBe => new TheForgettingTurnedOutToBe(
                stands: WhereTheWorkStands::answered(),
                refused: null,
                wouldGo: new HowWhatIsKeptReads()->this($forgetting->listed()),
                theWayInWent: false,
                isDone: false,
                gone: [],
                left: [],
            ),
            done: static function (ThePlacesGone $gone, APathLeft ...$left): TheForgettingTurnedOutToBe {
                $places = [];

                foreach ($gone as $place) {
                    $places[] = $place;
                }

                return new TheForgettingTurnedOutToBe(
                    stands: WhereTheWorkStands::answered(),
                    refused: null,
                    wouldGo: null,
                    theWayInWent: false,
                    isDone: true,
                    gone: $places,
                    left: array_map(static fn(APathLeft $one): APlaceLeftAsShown => new APlaceLeftAsShown(where: $one->where(), why: $one->why()), array_values($left)),
                );
            },
        );
    }

    private function without(WhereTheWorkStands $stands, bool $theWayInWent = false): TheForgettingTurnedOutToBe
    {
        return new TheForgettingTurnedOutToBe(
            stands: $stands,
            refused: null,
            wouldGo: null,
            theWayInWent: $theWayInWent,
            isDone: false,
            gone: [],
            left: [],
        );
    }
}
