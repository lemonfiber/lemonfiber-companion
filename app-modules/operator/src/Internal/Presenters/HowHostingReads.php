<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatRunsUnattended;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatKeepsRunningTurnedOutToBe;
use Modules\Operator\Internal\WhatTheMachineSaysInstead;

/**
 * What asking a machine what it keeps running produces, as the fields a screen draws.
 *
 * `F2` — data in, view model out. A listing is a value and an obstacle is a
 * value, so what the screen shows is stated in a test rather than arranged
 * behind a port.
 */
final readonly class HowHostingReads
{
    /**
     * This device no longer holds a session for that stack.
     *
     * No obstacle, because nothing was met: the app did not get as far as
     * asking. The obstacle screen is where this goes, and the empty keys are
     * what the template branches on.
     */
    public function signedOut(): WhatKeepsRunningTurnedOutToBe
    {
        return new WhatKeepsRunningTurnedOutToBe(
            went: HowTheReadingWent::theSessionEnded(),
            commands: [],
            keptBySaid: '',
            instead: '',
            missing: 0,
        );
    }

    /**
     * The machine answered, and this is what it keeps running.
     *
     * The count of what did not come back is taken off the listing rather than
     * off the rows, so it is decided where the standings are read and a fold
     * that dropped a row could never quietly turn a machine that lost two
     * services into one that lost one.
     */
    public function this(WhatRunsUnattended $running): WhatKeepsRunningTurnedOutToBe
    {
        $rows = [];

        foreach ($running as $one) {
            $rows[] = new HowAnUnattendedCommandReads()->in($one);
        }

        return new WhatKeepsRunningTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            commands: $rows,
            keptBySaid: $running->whatKeepsThem()->saidOnTheScreen(),
            instead: $this->whatToDoInstead($running),
            missing: $running->didNotComeBack()->count(),
        );
    }

    /**
     * It did not, and this is what the operator met.
     *
     * The keys come off {@see Obstacle}, which owns them — so an obstacle
     * gaining a seventh case needs no edit here and cannot be given a sentence
     * here that disagrees with the one another screen shows.
     */
    public function met(Obstacle $why): WhatKeepsRunningTurnedOutToBe
    {
        return new WhatKeepsRunningTurnedOutToBe(
            went: HowTheReadingWent::somethingStopped($why),
            commands: [],
            keptBySaid: '',
            instead: '',
            missing: 0,
        );
    }

    /**
     * The sentence a machine with no manager carries, or nothing.
     *
     * Empty where the machine does this itself, which is what the template
     * branches on. It is not a default: the value one layer down refuses a
     * blank instruction on the arm that has one, so an empty here can only mean
     * the other arm was taken.
     */
    private function whatToDoInstead(WhatRunsUnattended $running): string
    {
        return $running->whereItCannot(
            instead: static fn(string $what): WhatTheMachineSaysInstead
                => new WhatTheMachineSaysInstead($what),
            itself: static fn(): WhatTheMachineSaysInstead
                => new WhatTheMachineSaysInstead(''),
        )->said;
    }
}
