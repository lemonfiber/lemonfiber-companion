<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The two ways one service comes to reach another.
 *
 * The word, as a closed set. {@see HowItReaches} is the reading built from it,
 * and this is the vocabulary both that and the wire are spelled in —
 * {@see HowItSettled} is the same pairing one noun over.
 *
 * **The distinction is whose decision it was.** A capability is asked for, and
 * the core works out which service answers; a name is given, and the core wires
 * what it was told. A plugin may not do the second — being wired by name is the
 * operator's own act — so an app that rendered the two alike would present a
 * decision somebody made as one the stack worked out, which is the same failure
 * {@see WhoSettledIt} exists to prevent one level down.
 *
 * Written out here rather than derived from the contract for {@see Severity}'s
 * reason: this module may not read a file and may not know the SDK exists.
 */
enum HowItWasReached: string
{
    /** A capability was asked for, and the core decided what answers it. */
    case Asked = 'asked';

    /** A service was named outright, and the core wired what it was told. */
    case ByName = 'by-name';
}
