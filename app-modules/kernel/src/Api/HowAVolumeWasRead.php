<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which kind of reading the stack took of a volume, as the wire names it.
 *
 * {@see HowFreshAReadingIs} carries the reading itself, with the moment a
 * network share was taken; this is the closed set the wire chooses between.
 */
enum HowAVolumeWasRead: string
{
    /** Read off a local disk, true as of now. */
    case Live = 'live';

    /** Read across a network share, as last told. */
    case AsOf = 'as_of';
}
