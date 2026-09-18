<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * The five words a repeat can be described by.
 *
 * The PHP face of `HowOften` in Kotlin and Swift. A closed set on both sides of
 * the wire, because a word the native half does not recognise is a repeat that
 * will never happen — and the native half answers `no_such_repeat` rather than
 * arming a daily one instead.
 */
enum HowOften: string
{
    /** Every hour, on a minute. */
    case Hourly = 'hourly';

    /** Every day, at an hour and a minute. */
    case Daily = 'daily';

    /** Every week, on a day. */
    case Weekly = 'weekly';

    /** Every month, on a date. */
    case Monthly = 'monthly';

    /** Every year, on a date in a month. */
    case Yearly = 'yearly';
}
