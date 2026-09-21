<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use function count;

/**
 * What came back when the screen asked what the stack is set to.
 *
 * **How many there are is derived rather than carried.** An earlier draft
 * took the count off the listing and defended it as guarding against a fold
 * that dropped a row — but the fold has no branch in it, so `count($rows)`
 * and `count($set)` cannot differ, and two paragraphs defending an impossible
 * divergence are worse than no paragraphs. {@see WhatStoppedTurnedOutToBe}
 * carries its own because the wire tells it whether the listing is partial;
 * the `config` envelope says no such thing, so there is nothing here to carry.
 */
final readonly class WhatThisStackIsSetToTurnedOutToBe
{
    /** @param list<WhatOneSettingSays> $set */
    public function __construct(
        public HowTheReadingWent $went,
        public array $set = [],
    ) {}

    /**
     * How many settings there are to show.
     *
     * Counted here rather than passed in, so there is one number and no way
     * for it to disagree with the rows beside it.
     */
    public function howMany(): int
    {
        return count($this->set);
    }
}
