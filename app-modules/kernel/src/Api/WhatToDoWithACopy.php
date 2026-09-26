<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app may ask a stack to do about a copy of itself.
 *
 * A closed set for {@see WhatToChange}'s reason: an action's name is the last
 * segment of its path, and a name spelled at the call site is this app able to
 * ask for any action a stack offers.
 */
enum WhatToDoWithACopy: string
{
    /** Take a copy. */
    case Take = 'take';

    /** Put one back, or say what putting it back would do. */
    case PutBack = 'put_back';

    /** lemonfiber's word for it, which this app's word is allowed to differ from. */
    public function asked(): string
    {
        return match ($this) {
            self::Take => 'backup',
            self::PutBack => 'restore',
        };
    }
}
