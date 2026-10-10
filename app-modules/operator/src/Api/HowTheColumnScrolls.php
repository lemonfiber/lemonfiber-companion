<?php

declare(strict_types=1);

namespace Modules\Operator\Api;

/** How a screen's content column scrolls: from its top, from its end, or pulled down from its top to read it again. */
enum HowTheColumnScrolls
{
    case FromTheTop;

    /** Where what matters is last, and the screen is scrolled back from it. */
    case FromTheEnd;

    /** On a screen that asks the stack again, where pulling it down reads it again. */
    case PulledDownToReadAgain;
}
