<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the household's player may ask a stack to do.
 *
 * A closed set for {@see WhatToChange}'s reason: an action's name is the last
 * segment of its path, and a name spelled at the call site is this app able to
 * ask for any action a stack offers.
 */
enum WhatThePlayerAsks: string implements AnAction
{
    /** A grant to play on the member's own account, for this device. */
    case Grant = 'grant';

    public function asked(): string
    {
        return $this->value;
    }
}
