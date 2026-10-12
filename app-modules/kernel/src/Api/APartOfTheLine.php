<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Each part of how a stack shares its line that a change can write.
 */
enum APartOfTheLine: string
{
    case Down = 'down';

    case Up = 'up';

    case Active = 'active';

    case Line = 'line';

    case Cap = 'cap';

    case Exceeded = 'exceeded';
}
