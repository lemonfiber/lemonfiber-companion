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
     * @param WhatTheUpdateTakenCameTo|null $took   what the update taken here came to, where one was taken and the stack reported it
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?WhatTheUpdateTakenCameTo $took = null,
    ) {}
}
