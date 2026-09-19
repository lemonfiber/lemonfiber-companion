<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What came back when the screen asked what the stack is set to.
 *
 * **`howMany` is carried rather than counted off `$set`.** It comes from the
 * listing the stack sent, so a fold that dropped a row could not quietly turn
 * a short listing into a complete one — the same reason
 * {@see WhatStoppedTurnedOutToBe} carries how much is shown.
 */
final readonly class WhatThisStackIsSetToTurnedOutToBe
{
    /** @param list<WhatOneSettingSays> $set */
    public function __construct(
        public HowTheReadingWent $went,
        public array $set,
        public int $howMany,
    ) {}
}
