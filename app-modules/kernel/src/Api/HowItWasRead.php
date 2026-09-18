<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which of the two ways the operator got the pairing material in.
 *
 * Both are required: scanning a code, **and** typed entry where there is no
 * camera or the permission was declined. The second is not a fallback bolted on
 * for completeness — it is the route somebody takes on a device whose camera
 * they have refused this app, and every permission is optional with
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
    /** The camera read it. */
    case Scanned = 'scanned';

    /**
     * Somebody typed it.
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

    /**
     * The key for what the screen on this road is for.
     *
     * On the road rather than on the screen, and that is the whole point: a
     * screen spelling `connection.scan_the_code` is a literal, and a literal is
     * the shape `L7` names as the thing to cure. The road is a closed set with
     * two members and each has one opening sentence, so the key is built from
     * the case the way {@see Permission::reason()} builds
     * its own.
     *
     * **Why the road answers and the outcome does not.** What a screen says
     * once something has happened is the outcome's to name — a pairing that was
     * written down says so identically on both roads. What it says *before*
     * anything has happened is what that screen is *for*, and the two roads are
     * for different things: one points a camera and the other takes dictation.
     * An outcome cannot tell them apart; this can.
     */
    public function askedFor(): string
    {
        return sprintf('connection.%s_the_code', $this->value);
    }

    /** The line under it: how to get started on this road. */
    public function howToStart(): string
    {
        return sprintf('connection.%s_the_code_action', $this->value);
    }
}
