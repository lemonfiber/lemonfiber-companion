<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of the update taken from this screen, flattened for the template.
 *
 * Its own model rather than fields on {@see WhatTheUpkeepTurnedOutToBe},
 * because it is a different answer to a different question: the reading says
 * where the stack stands, and only the update's own report says how each
 * service took it.
 */
final readonly class HowTheLastUpdateWent
{
    /**
     * @param HowTheReadingWent               $went               whether asking after it came back, and what stood in the way where it did not
     * @param bool                            $wasTaken           whether an update was taken here, so there is anything to follow
     * @param bool                            $isWorking          whether the stack is still carrying it out
     * @param bool                            $hasEnded           whether the stack has no outcome for it any more
     * @param list<WhatOneServiceTookItSays>  $applied            what became of each service it touched, those not where the operator wanted them first
     * @param int                             $didNotArrive       how many of those are not where the operator wanted them
     * @param bool                            $anythingUnanswered whether the stack cannot say what some are doing
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $wasTaken,
        public bool $isWorking,
        public bool $hasEnded,
        public array $applied,
        public int $didNotArrive,
        public bool $anythingUnanswered,
    ) {}
}
