<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Removing everything lemonfiber keeps, by the verb the stack is asked whether it offers.
 */
enum ForgettingWhatIsKept: string implements AnAction
{
    case Forget = 'forget';

    public function asked(): string
    {
        return 'forget';
    }
}
