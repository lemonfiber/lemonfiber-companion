<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where removing everything lemonfiber keeps has got to, flattened for a template. */
final readonly class TheForgettingTurnedOutToBe
{
    /**
     * @param WhereTheWorkStands             $stands         where the work stands, and whether the stack could be asked at all
     * @param ARefusalAsShown|null          $refused      the stack's refusal, where it refused
     * @param WhatIsKeptTurnedOutToBe|null  $wouldGo      the stack's list of what would go, where it answered the asking without a yes
     * @param bool                          $theWayInWent whether the yes was given and the answer could not be read, which is this app's way in having gone with the rest
     * @param WhatForgettingRemoved|null    $removed      what went and what was left, where the stack reported it removed
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?ARefusalAsShown $refused = null,
        public ?WhatIsKeptTurnedOutToBe $wouldGo = null,
        public bool $theWayInWent = false,
        public ?WhatForgettingRemoved $removed = null,
    ) {}
}
