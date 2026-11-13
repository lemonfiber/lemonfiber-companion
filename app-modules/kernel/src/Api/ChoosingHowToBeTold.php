<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** Choosing which of the ways the operator offered tell a member that something they asked for has arrived. */
enum ChoosingHowToBeTold: string implements AnAction
{
    case Choose = 'choose';

    public function asked(): string
    {
        return match ($this) {
            self::Choose => 'notify-choose',
        };
    }
}
