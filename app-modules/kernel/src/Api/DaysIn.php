<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How many days make a year, declared once.
 *
 * A year of three hundred and sixty-five: the calendar counts a leap day
 * apart from it, and a year of keeping readings is this many days whether or
 * not one falls inside it.
 */
enum DaysIn: int
{
    case AYear = 365;
}
