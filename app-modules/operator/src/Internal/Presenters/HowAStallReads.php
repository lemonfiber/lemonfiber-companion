<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Unsupported;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatStoppedTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatTheQueueCouldNotReach;

/**
 * What asking a stack what has stopped produces, as the fields a screen draws.
 *
 * `F2` — data in, view model out. A listing is a value and an obstacle is a
 * value, so what the screen shows is stated in a test rather than arranged
 * behind a port.
 */
final readonly class HowAStallReads
{
    /** The count, where the stack looked at everything it manages. */
    public const string COUNTED = 'health.stuck_count';

    /** The count, where something it manages was out of its reach. */
    public const string COUNTED_WHERE_IT_LOOKED = 'health.stuck_count_where_it_looked';

    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. The obstacle screen is where this goes, and the empty keys are what
     * the template branches on.
     */
    public function signedOut(): WhatStoppedTurnedOutToBe
    {
        return new WhatStoppedTurnedOutToBe(
            went: HowTheReadingWent::theSessionEnded(),
            stalled: [],
            shownSaid: '',
            unreached: [],
            countSaid: self::COUNTED,
        );
    }

    /** The stack answered, and this is what has stopped. */
    public function these(Stalled $stalled): WhatStoppedTurnedOutToBe
    {
        $rows = [];

        foreach ($stalled as $one) {
            $rows[] = new HowAStalledItemReads()->in($one);
        }

        $unreached = [];

        foreach ($stalled->whatItCannotActOn() as $limit) {
            $unreached[] = $this->unreached($limit);
        }

        // The key comes off the listing rather than off the rows, so how much
        // is shown is decided where the stack said it and a fold that dropped a
        // row could never quietly turn a partial listing into a complete one.
        return new WhatStoppedTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            stalled: $rows,
            shownSaid: $stalled->howMuchIsShown()->saidOnTheScreen(),
            unreached: $unreached,
            countSaid: $unreached === [] ? self::COUNTED : self::COUNTED_WHERE_IT_LOOKED,
        );
    }

    /**
     * It did not, and this is what the operator met.
     *
     * The keys come off {@see Obstacle}, which owns them — so an obstacle
     * gaining a seventh case needs no edit here and cannot be given a sentence
     * here that disagrees with the one another screen shows.
     *
     * **A credential the stack refused is a signed-out app, not an obstacle.**
     * An identity removed from the household results in a
     * signed-out app at the next refused call and that nothing already loaded
     * goes on being rendered. {@see Obstacle::meansWeAreSignedOut()} draws that
     * line once, so this fold and every one beside it cannot come to disagree
     * about whether somebody is signed in.
     */
    public function met(Obstacle $why): WhatStoppedTurnedOutToBe
    {
        return new WhatStoppedTurnedOutToBe(
            went: HowTheReadingWent::somethingStopped($why),
            stalled: [],
            shownSaid: '',
            unreached: [],
            countSaid: self::COUNTED,
        );
    }

    private function unreached(Unsupported $limit): WhatTheQueueCouldNotReach
    {
        return new WhatTheQueueCouldNotReach(what: $limit->what(), because: $limit->because());
    }
}
