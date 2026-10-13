<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Making a set of forms the active set, by the verb the stack is asked whether it offers.
 */
enum SwitchingTheForms: string implements AnAction
{
    case Switch = 'switch';

    public function asked(): string
    {
        return 'switch';
    }
}
