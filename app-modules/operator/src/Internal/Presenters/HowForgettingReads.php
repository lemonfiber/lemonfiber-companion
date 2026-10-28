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
use Modules\Operator\Internal\ViewModels\WhatForgettingRemoved;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;

/**
 * Where removing everything lemonfiber keeps has got to, as the template draws it.
 */
final readonly class HowForgettingReads
{
    public function notAsked(): TheForgettingTurnedOutToBe
    {
        return new TheForgettingTurnedOutToBe(WhereTheWorkStands::notAsked());
    }

    public function signedOut(): TheForgettingTurnedOutToBe
    {
        return new TheForgettingTurnedOutToBe(WhereTheWorkStands::signedOut());
    }

    public function running(): TheForgettingTurnedOutToBe
    {
        return new TheForgettingTurnedOutToBe(WhereTheWorkStands::running());
    }

    public function ended(): TheForgettingTurnedOutToBe
    {
        return new TheForgettingTurnedOutToBe(WhereTheWorkStands::ended());
    }

    public function refused(ARefusalInItsWords $why): TheForgettingTurnedOutToBe
    {
        return new TheForgettingTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: new HowARefusalReads()->inItsWords($why),
        );
    }

    /** What stood in the way: before a yes, an obstacle; after one, this app's way in having gone with everything else. */
    public function met(Obstacle $why, bool $agreed): TheForgettingTurnedOutToBe
    {
        return $agreed
            ? new TheForgettingTurnedOutToBe(WhereTheWorkStands::answered(), theWayInWent: true)
            : new TheForgettingTurnedOutToBe(WhereTheWorkStands::met($why));
    }

    /** What the stack answered: the list of what would go, or what went. */
    public function answered(TheForgetting $forgetting): TheForgettingTurnedOutToBe
    {
        return $forgetting->got()->either(
            notAsked: static fn(): TheForgettingTurnedOutToBe => new TheForgettingTurnedOutToBe(WhereTheWorkStands::answered()),
            unconfirmed: static fn(): TheForgettingTurnedOutToBe => new TheForgettingTurnedOutToBe(
                stands: WhereTheWorkStands::answered(),
                wouldGo: new HowWhatIsKeptReads()->this($forgetting->listed()),
            ),
            done: static function (ThePlacesGone $gone, APathLeft ...$left): TheForgettingTurnedOutToBe {
                $places = [];

                foreach ($gone as $place) {
                    $places[] = $place;
                }

                return new TheForgettingTurnedOutToBe(
                    stands: WhereTheWorkStands::answered(),
                    removed: new WhatForgettingRemoved(
                        gone: $places,
                        left: array_map(static fn(APathLeft $one): APlaceLeftAsShown => new APlaceLeftAsShown(where: $one->where(), why: $one->why()), array_values($left)),
                    ),
                );
            },
        );
    }
}
