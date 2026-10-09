<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

/**
 * A moment far enough ahead that nothing a stand-in hands out has passed it.
 *
 * A stand-in session that had already ended, or pairing material that had
 * already expired, would take the first run with it, at a moment nobody would
 * connect to a date written here. Written rather than counted from a clock:
 * time is behind a port, and material assembled from the moment it was read
 * would make two runs of the same stand-in answer differently.
 *
 * Three spellings of the one moment, because a pairing code carries seconds
 * since the epoch, the wire carries an RFC 3339 stamp, and a grant carries the
 * day it lasts until. They sit together so that one is not changed without the
 * others, and a test holds them to the same moment.
 */
final readonly class LongAfterAnyRun
{
    /** The first moment of 2099, in seconds since the epoch. */
    public const int IN_SECONDS = 4_070_908_800;

    /** The same moment, as the wire writes a stamp. */
    public const string WRITTEN = '2099-01-01T00:00:00Z';

    /** The day that moment begins, as the wire writes a day. */
    public const string DAY = '2099-01-01';
}
