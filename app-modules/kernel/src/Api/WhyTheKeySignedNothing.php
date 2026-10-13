<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** Why this phone's key signed nothing. */
enum WhyTheKeySignedNothing
{
    /** The operator declined the face or fingerprint prompt. */
    case Cancelled;

    /** The key is gone, or a new face or fingerprint made it unusable: a new one has to be made. */
    case Gone;

    /** Something else went wrong; trying again is reasonable. */
    case Failed;
}
