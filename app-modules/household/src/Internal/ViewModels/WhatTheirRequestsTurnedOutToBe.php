<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * Both halves of one reading of what a member asked for, flattened for a template.
 *
 * Each half keeps its own outcome: a stack that said what somebody is owed and
 * could not say what they asked for has answered half, and the screen draws
 * each half as it went.
 */
final readonly class WhatTheirRequestsTurnedOutToBe
{
    public function __construct(
        public WhatAMemberTurnedOutToBeOwed $owed,
        public WhatAMemberTurnedOutToHaveAsked $asked,
    ) {}
}
