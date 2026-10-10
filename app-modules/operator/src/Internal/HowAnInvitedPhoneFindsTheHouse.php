<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Connection\Api\WhatTheCodeSaysSoFar;
use Modules\Kernel\Api\AJoinLink;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\JoinLinkCannotBeUsed;

use function parse_url;

use const PHP_URL_SCHEME;

/**
 * How a phone somebody was invited on finds their house: the one place what it is handed is named and read.
 *
 * It is the code on their invitation, which is its join link, read with the
 * camera or opened from wherever it was sent; or, where they were given none,
 * the code whoever runs the house shows for a new phone.
 */
enum HowAnInvitedPhoneFindsTheHouse: string
{
    case YourInvitation = 'your_invitation';

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

    /** What the phone was handed, read as a join link where it is written as one and as the code for a new phone where it is not. */
    public function read(string $handed, Clock $clock): WhatThePhoneWasHanded
    {
        return parse_url($handed, PHP_URL_SCHEME) === AJoinLink::scheme() ? self::aLink($handed, $clock) : self::aCode($handed, $clock);
    }

    private static function aLink(string $handed, Clock $clock): WhatThePhoneWasHanded
    {
        try {
            return WhatThePhoneWasHanded::aLink(AJoinLink::read($handed, $clock));
        } catch (JoinLinkCannotBeUsed) {
            return WhatThePhoneWasHanded::nothingUsable(WhatFindingTheHouseMet::LinkUnusable);
        }
    }

    private static function aCode(string $handed, Clock $clock): WhatThePhoneWasHanded
    {
        return WhatTheCodeSaysSoFar::read($handed, HowItWasRead::Scanned, $clock)->material(
            read: WhatThePhoneWasHanded::aCode(...),
            notYet: static fn(): WhatThePhoneWasHanded => WhatThePhoneWasHanded::nothingUsable(WhatFindingTheHouseMet::CodeUnreadable),
        );
    }
}
