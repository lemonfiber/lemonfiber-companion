<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Internal;

/** How far a wake got with one stack: still to hear from, or heard out. */
enum HowFarItGot
{
    /** It has not yet said its first summary, so what it raised meanwhile may still be on its way. */
    case StillSpeaking;

    /** It said its first summary, its stream ended, or it could not be heard. */
    case HeardOut;
}
