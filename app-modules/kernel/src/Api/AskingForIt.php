<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A member asking for a title their search found, as the action the core is asked by.
 */
enum AskingForIt: string implements AnAction
{
    case AskForIt = 'ask_for_it';

    public function asked(): string
    {
        return match ($this) {
            self::AskForIt => 'ask',
        };
    }
}
