<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Changing how a stack shares its line, by the verb the stack is asked whether it offers.
 */
enum ChangingTheLine: string implements AnAction
{
    case Change = 'change';

    public function asked(): string
    {
        return 'bandwidth';
    }
}
