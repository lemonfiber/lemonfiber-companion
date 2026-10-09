<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where playback stands, and why it stopped where it stopped and cannot go on.
 *
 * Why it stopped is a case of its own rather than a reason beside a stopped
 * state, so that nothing reading one can have a stop without its reason.
 */
enum PlaybackIs
{
    /** The player is on screen and has not started yet. */
    case Opening;

    case Playing;

    case Paused;

    /** It is waiting for the door, and stops if the wait runs long. */
    case Stalled;

    /** It played to the end. */
    case Ended;

    /** There is no player on screen. */
    case Closed;

    /** The door did not answer, or stopped answering: the library is out of reach from here. */
    case StoppedOutOfReach;

    /** Something answered at the door's address with another certificate. */
    case StoppedByThePin;

    /** The device cannot play what the door sent. */
    case StoppedOnTheFormat;

    /** The door refused the grant. */
    case StoppedAtTheDoor;
}
