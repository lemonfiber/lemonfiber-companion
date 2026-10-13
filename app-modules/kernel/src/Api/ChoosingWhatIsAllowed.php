<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Choosing what the household may ask for, by the verb the stack is asked whether it offers.
 */
enum ChoosingWhatIsAllowed: string implements AnAction
{
    case Allow = 'allow';

    public function asked(): string
    {
        return 'household-allow';
    }
}
