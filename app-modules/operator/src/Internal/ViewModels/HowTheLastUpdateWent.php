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
     * @param WhereTheWorkStands               $stands               where the work stands, and whether the stack could be asked at all
     * @param bool                            $wasTaken           whether an update was taken here, so there is anything to follow
     * @param list<WhatOneServiceTookItSays>  $applied            what became of each service it touched, those not where the operator wanted them first
     * @param int                             $didNotArrive       how many of those are not where the operator wanted them
     * @param bool                            $anythingUnanswered whether the stack cannot say what some are doing
     * @param list<AMoveInPlaceAsShown>       $moved              what became of each move in place it tried
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public bool $wasTaken,
        public array $applied,
        public int $didNotArrive,
        public bool $anythingUnanswered,
        public array $moved,
    ) {}
}
