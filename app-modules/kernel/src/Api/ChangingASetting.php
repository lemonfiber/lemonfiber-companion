<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Changing one setting a stack's services read, by the verb the stack is asked whether it offers.
 */
enum ChangingASetting: string implements AnAction
{
    /** Give the setting a value of the operator's. */
    case Set = 'set';

    /** Put the setting back to its default. */
    case Clear = 'clear';

    public function asked(): string
    {
        return match ($this) {
            self::Set => 'settings-set',
            self::Clear => 'settings-clear',
        };
    }
}
