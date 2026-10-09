<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

/** Where playback stands, in the word both native halves answer with. */
enum WherePlaybackStands: string
{
    /** The player is on screen and has not started yet. */
    case Opening = 'opening';

    case Playing = 'playing';

    case Paused = 'paused';

    /** It is waiting for the door, and will stop if the wait runs long. */
    case Stalled = 'stalled';

    /** It played to the end. */
    case Ended = 'ended';

    /** It stopped and could not go on; why is beside it. */
    case Stopped = 'stopped';

    /** There is no player on screen. */
    case Closed = 'closed';

    /** What the bridge said, or that there is no player where it said nothing this type knows. */
    public static function orClosed(?string $said): self
    {
        if ($said === null) {
            return self::Closed;
        }

        return self::tryFrom($said) ?? self::Closed;
    }
}
