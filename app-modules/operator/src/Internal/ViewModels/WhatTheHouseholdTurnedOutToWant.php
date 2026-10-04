<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * What asking a stack what the house wants produced, flattened for a template.
 *
 * The sibling of {@see WhatTheStackTurnedOutToBe} and written the same way:
 * {@see \Modules\Operator\Internal\Presenters\HowTheHouseholdsAskingReads}
 * folds the answer once and the template reads fields, because Blade has no
 * `either()` and cannot be given one.
 *
 * **Three states, and the empty list is one of them.** A household that has
 * asked for nothing is the ordinary state of a quiet week; a session that has
 * ended is the sign-in screen; an obstacle is its own. Folding the first two
 * together would have a signed-out phone say *nobody has asked for anything*,
 * which is the collapse {@see \Modules\Kernel\Api\WhatWasWanted} refuses one
 * layer up and this one must not rebuild.
 *
 * **A reading the phone kept is drawn whole, and waits.** It came back when it
 * was read, so it is drawn as a reading; what stopped the asking on this frame,
 * where something did, is a fact of its own beside it. Until a fresh reading
 * arrives, every decision on it is drawn and cannot be made, with how long ago
 * the reading was read beside it.
 */
final readonly class WhatTheHouseholdTurnedOutToWant
{
    /**
     * @param list<WhatOneRequestSays> $requests every request the house has made, in the stack's order
     * @param int                      $waiting  how many of them want a decision
     * @param HowTheReadingWent        $askedNow what this frame's asking met, which stands beside a kept reading where the stack did not answer
     * @param AgoAsShown               $readAgo  how long ago the reading drawn was read, said only where it was kept
     * @param bool                     $waitsForTheStack whether the reading drawn is one the phone kept, so nothing on it can be decided yet
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $requests,
        public int $waiting,
        public HowTheReadingWent $askedNow,
        public AgoAsShown $readAgo,
        public bool $waitsForTheStack,
    ) {}
}
