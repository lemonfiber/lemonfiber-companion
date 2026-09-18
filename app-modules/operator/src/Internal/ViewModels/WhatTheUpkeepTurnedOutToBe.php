<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Services;

/**
 * What asking a stack where it stands produced, flattened for a template.
 *
 * **Three states, and *nothing waiting* is one of them.** A stack that is
 * current is the answer an operator wants; a session that has ended is
 * the sign-in screen; an obstacle is its own. Folding the first two together
 * would have a signed-out phone report a house that is up to date, which is the
 * collapse {@see \Modules\Kernel\Api\WhatIsCurrent} refuses one layer up.
 *
 * **What the stack said about itself is carried in every state.** A screen that
 * could reach a listing without it is a screen that can claim to be current by
 * accident, so the states with no listing carry the key that says so and the
 * template renders one line rather than branching on whether a field is there.
 */
final readonly class WhatTheUpkeepTurnedOutToBe
{
    /**
     * @param string                   $howSaid    the key for whether the stack is current, pending or stale
     * @param string                   $running    the version in use, or empty where the stack named none
     * @param bool                     $runningWasWithdrawn whether the version in use has been taken back
     * @param list<WhatOneReleaseSays>        $waiting    the releases worth offering, in the stack's order
     * @param Services                        $changing   the services taking one would change
     * @param list<WhatOneServiceTookItSays>  $applied    what became of each service the last update touched
     * @param int                             $didNotArrive how many of those are not where the operator wanted them
     * @param bool                            $anythingUnanswered whether the stack cannot say what some are doing
     * @param bool                            $canTakeOne whether there is an update here to offer at all
     * @param bool                            $anyWorthNoticing whether any release waiting is one the household would see
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $howSaid,
        public string $running,
        public bool $runningWasWithdrawn,
        public array $waiting,
        public Services $changing,
        public array $applied,
        public int $didNotArrive,
        public bool $anythingUnanswered,
        public bool $canTakeOne,
        public bool $anyWorthNoticing,
    ) {}
}
