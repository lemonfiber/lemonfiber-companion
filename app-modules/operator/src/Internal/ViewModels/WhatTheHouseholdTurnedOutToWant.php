<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

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
 * ended is `N1-R44`'s screen; an obstacle is `N1-R10`'s. Folding the first two
 * together would have a signed-out phone say *nobody has asked for anything*,
 * which is the collapse {@see \Modules\Kernel\Api\WhatWasWanted} refuses one
 * layer up and this one must not rebuild.
 */
final readonly class WhatTheHouseholdTurnedOutToWant
{
    /**
     * @param list<WhatOneRequestSays> $requests every request the house has made, in the stack's order
     * @param int                      $waiting  how many of them want a decision (`N2-R11`)
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $requests,
        public int $waiting,
    ) {}
}
