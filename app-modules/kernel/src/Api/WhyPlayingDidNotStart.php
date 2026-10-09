<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why the player did not come on screen.
 *
 * Two, because a member can do something different about each: nothing on a
 * device with no player, and telling whoever looks after the house where what
 * the core stated could not be played. Which part of the core's statement the
 * device refused is not a member's business, and they are never shown it.
 */
enum WhyPlayingDidNotStart
{
    /** The device refused what the core stated: an address, a certificate or a grant it would not play. */
    case WhatTheHouseSaidCannotBePlayed;

    /** There is no player on this device. */
    case ThereIsNoPlayerHere;
}
