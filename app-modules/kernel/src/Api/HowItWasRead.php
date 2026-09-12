<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which of the two ways the operator got the pairing material in.
 *
 * `N1-R6` requires both: scanning a code, **and** typed entry where there is no
 * camera or the permission was declined. The second is not a fallback bolted on
 * for completeness — it is the route somebody takes on a device whose camera
 * they have refused this app, and `N4-R3` says every permission is optional with
 * a working alternative. This is that alternative, named.
 *
 * Recorded rather than discarded because the two routes fail differently and a
 * screen has to say so. A scan that produced nonsense is a camera pointed at the
 * wrong thing, and the remedy is to try again. The same nonsense typed is a
 * transcription error, and the remedy is to check the characters. Telling
 * somebody to "try again" when they have just typed sixty-four characters
 * correctly is the screen this enum exists to prevent.
 *
 * What it does **not** do is change how the material is parsed. Both routes
 * carry the same payload and are held to the same rules — a code read by camera
 * is not more trusted than one read by a person, and a parser that branched on
 * this would be two parsers, of which only one would stay tested.
 */
enum HowItWasRead: string
{
    /** The camera read it (`N1-R6`). */
    case Scanned = 'scanned';

    /**
     * Somebody typed it (`N1-R6`, `N4-R3`).
     *
     * The route that has to work on a device with no camera, and on one whose
     * operator declined the permission.
     */
    case Typed = 'typed';

    /**
     * Whether a second attempt is likely to go differently on its own.
     *
     * True for a scan — a camera can be re-pointed, re-focused, moved into
     * better light — and false for typing, where "try again" is advice somebody
     * has already taken. What a mistyped code needs is to be shown what did not
     * parse, which is a different screen.
     */
    public function isWorthSimplyRetrying(): bool
    {
        return $this === self::Scanned;
    }
}
