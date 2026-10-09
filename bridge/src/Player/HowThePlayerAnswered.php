<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

use Lemonfiber\Native\WhatTheBridgeAnswered;

/** The words the player answers a call with where it did what it was asked. */
enum HowThePlayerAnswered: string
{
    /** The player is on screen and showing the title. */
    case Showing = 'showing';

    /** The player carried out a command. */
    case Done = 'done';

    /** The player said where it stands. */
    case Said = 'said';

    /**
     * What the player answered under `outcome`, where it is one of these.
     *
     * Two branches rather than `tryFrom($said ?? '')`, for the reason
     * {@see \Lemonfiber\Native\WhyNothingWasKept::orTheStoreWouldNotOpen()} gives.
     */
    public static function in(WhatTheBridgeAnswered $said): ?self
    {
        $word = $said->outcome();

        return $word === null ? null : self::tryFrom($word);
    }
}
