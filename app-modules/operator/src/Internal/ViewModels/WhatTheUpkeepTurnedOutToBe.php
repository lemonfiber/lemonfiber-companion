<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\TakingAnUpdate;

/**
 * What asking a stack where it stands produced, flattened for a template.
 *
 * **Three states, and *nothing waiting* is one of them.** A stack that is
 * current is the answer an operator wants; a session that has ended is
 * the sign-in screen; an obstacle is its own. Folding the first two together
 * would have a signed-out phone report a house that is up to date, which is the
 * collapse {@see \Modules\Kernel\Api\WhatIsCurrent} refuses one layer up.
 *
 * **The offer is the update itself, or nothing.** It is built from a reading
 * that said an update is available, so a template cannot offer one the stack
 * did not report — and what the confirmation names is what the offer carries.
 */
final readonly class WhatTheUpkeepTurnedOutToBe
{
    /**
     * @param string                         $pinsSaid   the key for where the services stand against their pins
     * @param string                         $running    the version in use, or a dash where the stack named none
     * @param bool                           $runningWasWithdrawn whether the version in use has been taken back
     * @param ?WhatOneReleaseSays            $inUse      what the release in use changed, where the stack named it
     * @param list<WhatOneReleaseSays>       $history    every release the stack's record holds, newest first
     * @param ?TakingAnUpdate                $offer      the update to take, where the stack offered one
     * @param list<WhatOneServiceTookItSays> $applied    what became of each service the last update touched
     * @param int                            $didNotArrive how many of those are not where the operator wanted them
     * @param bool                           $anythingUnanswered whether the stack cannot say what some are doing
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $pinsSaid,
        public string $running,
        public bool $runningWasWithdrawn,
        public ?WhatOneReleaseSays $inUse,
        public array $history,
        public ?TakingAnUpdate $offer,
        public array $applied,
        public int $didNotArrive,
        public bool $anythingUnanswered,
    ) {}
}
