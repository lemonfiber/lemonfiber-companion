<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** Why this phone holds no key that proves its operator. */
enum WhyThisPhoneHoldsNoKey
{
    /** It has nowhere in hardware to keep one. */
    case NoHardware;

    /** No face or fingerprint is set up, so nothing could guard one. */
    case NoBiometric;

    /** It made the key in software, so the key was thrown away: a stack refuses one kept there. */
    case InSoftware;

    /** Its platform would not vouch for where it made the key. */
    case Unattested;

    /** Something else went wrong; trying again is reasonable. */
    case Failed;
}
