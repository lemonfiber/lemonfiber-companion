<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Why the sheet was not put in front of anybody.
 *
 * Two refusals and they are not the same sentence. *There was nothing to hand
 * over* is this application's own fault and is answered by assembling the
 * report again; *the platform would not* is the device's, and is answered by
 * trying again. A boolean cannot carry that, and a screen behind one has to
 * guess which it was.
 */
enum WhyNothingWasHandedOver: string
{
    /** There was nothing to offer. Nothing was put in front of anybody. */
    case NothingToHandOver = 'nothing_to_hand_over';

    /** There was, and the sheet could not be presented. */
    case ThePlatformWouldNot = 'the_platform_would_not';
}
