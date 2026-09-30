<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How many seconds make a minute, an hour and a day, declared once.
 *
 * Every length of time here is counted in the seconds an {@see Instant}
 * counts, and every class that turns one into a minute, an hour or a day
 * asks this rather than writing sixty down again. Backed by the count, so a
 * constant can be declared from one: `30 * SecondsIn::ADay->value` is thirty
 * days, and nothing else in the application has to know what a day is.
 *
 * A day is always eighty-six thousand four hundred of them here. Nothing in
 * this application counts across a change of the clocks, and an instant is a
 * count from the epoch, which a change of the clocks does not touch.
 */
enum SecondsIn: int
{
    case AMinute = 60;

    case AnHour = 3_600;

    case ADay = 86_400;
}
