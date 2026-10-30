<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/** Why a stack said nothing of what it serves. */
enum WhyNothingWasDeclared
{
    /** It has no way to say, so it is older than the declaration. */
    case TooOldToSay;

    /** It could not be asked, or what it said could not be read. */
    case Unasked;
}
