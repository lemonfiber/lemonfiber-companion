<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Accepting a warning about a choice, by the verb the stack is asked whether it offers.
 */
enum AcceptingAChoice: string implements AnAction
{
    case Accept = 'accept';

    public function asked(): string
    {
        return 'accept';
    }
}
