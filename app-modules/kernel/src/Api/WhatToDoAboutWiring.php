<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app may ask a stack to do about how its services are wired to each other.
 *
 * A closed set for {@see WhatToChange}'s reason: an action's name is the last
 * segment of its path, and a name spelled at the call site is this app able to
 * ask for any action a stack offers.
 */
enum WhatToDoAboutWiring: string
{
    /** Wire them, or finish what an earlier run could not. */
    case Wire = 'wire';

    /** Choose which service fills a capability, or work out what that would come to. */
    case Fill = 'fill';

    /** lemonfiber's word for it, which this app's word is allowed to differ from. */
    public function asked(): string
    {
        return match ($this) {
            self::Wire => 'seed',
            self::Fill => 'wiring-fill',
        };
    }
}
