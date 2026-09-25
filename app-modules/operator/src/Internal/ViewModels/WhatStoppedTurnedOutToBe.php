<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack what has stopped produced, flattened for a template.
 *
 * The sibling of {@see WhatTheHouseholdTurnedOutToWant} and written the same
 * way: {@see \Modules\Operator\Internal\Presenters\HowAStallReads} folds the
 * answer once and the template reads fields, because Blade has no `either()`
 * and cannot be given one.
 *
 * **Three states, and the empty listing is one of them.** A stack with nothing
 * stuck is the answer an operator wants; a session that has ended is the sign-in
 * screen; an obstacle is its own. Folding the first two together would have
 * a signed-out phone report a house where everything is arriving normally,
 * which is the collapse {@see \Modules\Kernel\Api\WhatIsStuck} refuses one
 * layer up and this one must not rebuild.
 *
 * **How much is shown is carried in every state.** A screen that could reach a
 * listing without it is a screen that can claim to be complete by accident, and
 * the states that have no listing carry the key that says so — so the template
 * renders one line rather than branching on whether the field is there.
 *
 * **What the stack could not reach sits beside the listing.** An empty listing
 * from a queue the stack could not look into is not a house where everything is
 * arriving, so where anything was out of reach the count is said as a count of
 * what it could look at, and the template does not say that nothing stopped.
 */
final readonly class WhatStoppedTurnedOutToBe
{
    /**
     * @param list<WhatOneStalledItemSays>    $stalled   everything that stopped, in the stack's order
     * @param string                         $shownSaid the key for how much of the listing this is
     * @param list<WhatTheQueueCouldNotReach> $unreached what the stack could not look into, in its order
     * @param string                         $countSaid the key the count is said with
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $stalled,
        public string $shownSaid,
        public array $unreached,
        public string $countSaid,
    ) {}
}
