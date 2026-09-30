<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The lengths of time App settings offers to keep readings for, in days.
 *
 * The four offered by name. Any other count of days from
 * {@see HowLongReadingsAreKept::FEWEST_DAYS} to {@see HowLongReadingsAreKept::MOST_DAYS}
 * can be asked for as well, and keeping a reading until it is removed is
 * {@see HowLongReadingsAreKept::untilRemoved()}.
 */
enum KeptFor: int
{
    case SevenDays = 7;

    case ThirtyDays = 30;

    case NinetyDays = 90;

    case OneYear = DaysIn::AYear->value;
}
