<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

/**
 * Why the device would not put the player on screen.
 *
 * Each is a fault in what the core stated or in how it reached the device,
 * never something a member can fix by trying again, so each is answered by
 * telling the household's operator rather than the member.
 */
enum WhyThePlayerDidNotOpen: string
{
    /** The location is not an `https` address with a host and no credentials. */
    case NotAtADoor = 'not_at_a_door';

    /** The fingerprint is not one: there is nothing to pin the door to. */
    case Unpinned = 'unpinned';

    /** There is no grant, or it is not one a header can carry. */
    case NoGrant = 'no_grant';

    /** The grant was written into the address, where logs and caches keep it. */
    case GrantInTheAddress = 'grant_in_the_address';

    /** There is no place to start from. */
    case NoStartingPoint = 'no_starting_point';

    /** Nothing the player was built with could play from that location. */
    case Refused = 'refused';

    /** No player answered: every machine that is not a handset. */
    case NoPlayerHere = 'no_player';

    /** What the bridge said, or that no player answered where it said nothing this type knows. */
    public static function orNoPlayerHere(?string $said): self
    {
        if ($said === null) {
            return self::NoPlayerHere;
        }

        return self::tryFrom($said) ?? self::NoPlayerHere;
    }
}
