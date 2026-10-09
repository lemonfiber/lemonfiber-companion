<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

/** Why playback stopped and could not go on, in the word both native halves answer with. */
enum WhyPlaybackStopped: string
{
    /** The door did not answer, or stopped answering for longer than the player waits. */
    case Unreachable = 'unreachable';

    /** Something answered at the door's address with another certificate. */
    case PinMismatch = 'pin_mismatch';

    /** The device cannot play what the door sent. */
    case UnsupportedFormat = 'unsupported_format';

    /** The door refused the grant, or something named an address off the door. */
    case Refused = 'refused';

    /**
     * What the bridge said, or nothing where it said nothing.
     *
     * An empty word is the bridge saying playback has not stopped. A word this
     * type does not know is a reason it cannot name, and reads as the door being
     * out of reach, which is the one whose remedy is to try again.
     */
    public static function orNone(?string $said): ?self
    {
        if ($said === null || $said === '') {
            return null;
        }

        return self::tryFrom($said) ?? self::Unreachable;
    }
}
