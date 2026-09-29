<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Why a store hands back no reading to open: it keeps none, or it keeps one it cannot read.
 *
 * Two answers with two things to do about them, which is why neither is left
 * to a flag: nothing kept is a screen with nothing to draw yet, and a reading
 * this build cannot read is a row its owner lets go of.
 */
enum WhyNoReadingIsFound
{
    /** Nothing is kept for the stack. */
    case NoneIsKept;

    /** A reading is kept, in a shape this build has no name for or in columns it did not write. */
    case ItCannotBeRead;
}
