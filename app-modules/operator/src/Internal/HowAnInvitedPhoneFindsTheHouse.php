<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Connection\Api\WhatTheCodeSaysSoFar;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\HowItWasRead;

/**
 * How a phone somebody was invited on finds their house: the one place what it is handed is named and read.
 *
 * It is the code whoever runs the house shows for a new phone, read with the
 * camera. A way of finding the house that is added here is the one the
 * member's way in offers, and nothing else about that way in changes.
 */
enum HowAnInvitedPhoneFindsTheHouse: string
{
    case TheCodeForANewPhone = 'the_code_for_a_new_phone';

    /** The key for what the step asks, as its title. */
    public function said(): string
    {
        return InTheWayInsWords::said($this->value);
    }

    /** The key for how to do it. */
    public function explained(): string
    {
        return InTheWayInsWords::explained($this->value);
    }

    /** What the phone was handed, read as the pairing it carries. */
    public function read(string $handed, Clock $clock): WhatTheCodeSaysSoFar
    {
        return WhatTheCodeSaysSoFar::read($handed, HowItWasRead::Scanned, $clock);
    }
}
