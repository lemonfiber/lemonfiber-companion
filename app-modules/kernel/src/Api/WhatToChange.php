<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app may ask a stack to change about its own configuration.
 *
 * An action's name is the last segment of the path it is asked for, so a
 * name spelled as a literal at the call site is this app able to ask a stack
 * for *any* action it offers — `setup` among them, and the ones that write a
 * credential. A closed set is what makes the reach of this app readable from
 * one file instead of from every adapter, which is the reason
 * {@see WhatToDoWithIt} and {@see WhatWasDecided} exist.
 */
enum WhatToChange: string
{
    /** Put a value in one setting. */
    case Setting = 'setting';

    /** Put every file and connection the operator edited back to lemonfiber's own, or say what that would revert. */
    case BackToItsOwn = 'back_to_its_own';

    /**
     * lemonfiber's word for it.
     *
     * Apart from the case's own value for the reason {@see WhatWasDecided}
     * gives: the two vocabularies are allowed to differ, and this app's word
     * for a thing is not the wire's.
     */
    public function asked(): string
    {
        return match ($this) {
            self::Setting => 'config-set',
            self::BackToItsOwn => 'reset',
        };
    }
}
