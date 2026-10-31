<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a choice of filler would leave: each capability left unfilled with the service that would lose it, and what the part it stops holds that does not carry over.
 *
 * Both are what the operator weighs before the yes, and the stack works both
 * out before anything is written.
 */
final readonly class WhatTheChoiceLeaves
{
    private function __construct(private WhatNothingFills $unfilled, private WhatStaysBehind $behind) {}

    /** These capabilities left unfilled, and these things left behind. */
    public static function of(WhatNothingFills $unfilled, WhatStaysBehind $behind): self
    {
        return new self($unfilled, $behind);
    }

    /** Each capability left unfilled, with the service that would lose it. */
    public function unfilled(): WhatNothingFills
    {
        return $this->unfilled;
    }

    /** What the part the choice stops holds that its replacement is not carried. */
    public function behind(): WhatStaysBehind
    {
        return $this->behind;
    }
}
